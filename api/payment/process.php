<?php
/**
 * Payment Processing API Endpoint
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Create a debug log function
function debugLog($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logEntry .= " - " . json_encode($data);
    }
    file_put_contents(__DIR__ . '/../../logs/payment-debug.log', $logEntry . "\n", FILE_APPEND);
}

// Start logging
debugLog("Payment API called with method: " . $_SERVER['REQUEST_METHOD']);

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$inputRaw = file_get_contents('php://input');
debugLog("Raw input received", $inputRaw);

$input = json_decode($inputRaw, true);
debugLog("Decoded input", $input);

// Validate input
if (!isset($input['payment_method_id']) || !isset($input['package_id'])) {
    debugLog("Missing required parameters", $input);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get user ID from session
session_start();
debugLog("Session data", $_SESSION);

if (!isset($_SESSION['user_id'])) {
    debugLog("User not authenticated");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$paymentMethodId = $input['payment_method_id'];
$packageId = $input['package_id'];
$couponCode = $input['coupon_code'] ?? null;

debugLog("Processing payment for user: $userId, package: $packageId", [
    'payment_method_id' => $paymentMethodId,
    'coupon_code' => $couponCode
]);

// Include required files
try {
    debugLog("Loading required files");
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/stripe.php';
    require_once __DIR__ . '/../../models/Payment.php';
    require_once __DIR__ . '/../../models/Coupon.php';
    require_once __DIR__ . '/../../models/User.php';
    debugLog("Required files loaded successfully");
} catch (Exception $e) {
    debugLog("Failed to load required files: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    exit;
}

// Ensure we have a database connection
global $pdo;
if (!isset($pdo)) {
    debugLog("Database connection not established from global. Attempting to create a connection.");
    // Try to get a connection directly
    $pdo = getDbConnection();
    
    // If still no connection, retry a few times
    $maxRetries = 3;
    $retryCount = 0;
    $retryDelay = 1; // seconds
    
    while (!$pdo && $retryCount < $maxRetries) {
        debugLog("Retry " . ($retryCount + 1) . " for database connection");
        sleep($retryDelay);
        $pdo = getDbConnection();
        $retryCount++;
    }
    
    if (!$pdo) {
        debugLog("Failed to establish database connection after " . $maxRetries . " retries");
        http_response_code(503);
        echo json_encode([
            'success' => false, 
            'message' => 'Service temporarily unavailable. Database connection failed. Please try again later.',
            'error_code' => 'db_connection_error'
        ]);
        exit;
    }
    
    debugLog("Database connection established successfully");
}

try {
    // Initialize Stripe
    debugLog("Initializing Stripe");
    if (!function_exists('initStripe')) {
        throw new Exception('initStripe function not found. Check if Stripe configuration is properly included.');
    }
    initStripe();
    
    // Get package details
    debugLog("Getting package details for ID: $packageId");
    if (!function_exists('getCreditPackageById')) {
        throw new Exception('getCreditPackageById function not found. Check if required models are properly included.');
    }
    
    $package = getCreditPackageById($packageId);
    if (!$package) {
        throw new Exception('Invalid package selected');
    }
    
    debugLog("Package details retrieved", $package);
    
    // Calculate amount
    $amount = $package['price'];
    $credits = $package['credits'];
    
    debugLog("Base amount: $amount, credits: $credits");
    
    // Apply coupon if provided
    $discountAmount = 0;
    if ($couponCode) {
        debugLog("Processing coupon code: $couponCode");
        if (!function_exists('getCouponByCode')) {
            throw new Exception('getCouponByCode function not found. Check if required models are properly included.');
        }
        
        $coupon = getCouponByCode($couponCode);
        debugLog("Coupon result", $coupon);
        
        if ($coupon && function_exists('isCouponValid') && isCouponValid($coupon)) {
            debugLog("Coupon is valid, applying discount");
            
            if (!function_exists('applyCouponDiscount')) {
                throw new Exception('applyCouponDiscount function not found. Check if required functions are properly included.');
            }
            
            $amount = applyCouponDiscount($amount, $coupon);
            $discountAmount = $package['price'] - $amount;
            
            debugLog("Coupon applied, new amount: $amount, discount: $discountAmount");
        } else {
            debugLog("Coupon is invalid or expired", $coupon);
        }
    }
    
    // Convert to cents for Stripe
    $amountInCents = round($amount * 100);
    debugLog("Amount in cents for Stripe: $amountInCents");
    
    // Create payment intent
    debugLog("Creating payment intent");
    try {
        // Get the user's Stripe customer ID or create one if it doesn't exist
        $user = getUserById($userId);
        $stripeCustomerId = $user['stripe_customer_id'] ?? null;
        
        // If user doesn't have a Stripe customer ID, create one
        if (empty($stripeCustomerId)) {
            debugLog("User has no Stripe customer ID. Creating one...");
            
            // Get user email for Stripe customer creation
            $customer = \Stripe\Customer::create([
                'email' => $user['email'],
                'name' => $user['name'],
                'metadata' => [
                    'user_id' => $userId
                ]
            ]);
            
            $stripeCustomerId = $customer->id;
            
            // Save customer ID to user record
            $updateStmt = $pdo->prepare("UPDATE users SET stripe_customer_id = :stripe_customer_id WHERE id = :id");
            $updateResult = $updateStmt->execute([
                'stripe_customer_id' => $stripeCustomerId,
                'id' => $userId
            ]);
            
            if (!$updateResult) {
                debugLog("Failed to save Stripe customer ID for user", [
                    'user_id' => $userId,
                    'stripe_customer_id' => $stripeCustomerId,
                    'error' => $updateStmt->errorInfo()
                ]);
            } else {
                debugLog("Saved new Stripe customer ID for user", [
                    'user_id' => $userId,
                    'stripe_customer_id' => $stripeCustomerId
                ]);
            }
        } else {
            debugLog("Using existing Stripe customer ID", [
                'user_id' => $userId,
                'stripe_customer_id' => $stripeCustomerId
            ]);
        }
        
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'payment_method' => $paymentMethodId,
            'customer' => $stripeCustomerId, // Add customer ID to associate with the user
            'confirm' => true,
            'confirmation_method' => 'manual',
            'setup_future_usage' => 'off_session', // Add this to properly save the payment method for future use
            'return_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/index.php?page=payment-success",
            'metadata' => [
                'user_id' => $userId,
                'package_id' => $packageId,
                'credits' => $credits,
                'coupon_code' => $couponCode
            ]
        ]);
        
        debugLog("Payment intent created", [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'client_secret' => substr($paymentIntent->client_secret, 0, 10) . '...' // Log partial for security
        ]);
        
        // Attach the payment method to the customer for future use if not already attached
        try {
            debugLog("Attaching payment method to customer", [
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $stripeCustomerId
            ]);
            
            // For new customers, we need to explicitly attach the payment method
            try {
                $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                
                // Always attempt to attach - if it's already attached, Stripe will handle it correctly
                $paymentMethod->attach(['customer' => $stripeCustomerId]);
                debugLog("Payment method attached to customer");
                
                // Check if this is the customer's first payment method or if there's no default yet
                // If there are no other payment methods or if customer has no default, set this one as default
                $customer = \Stripe\Customer::retrieve($stripeCustomerId);
                $existingMethods = \Stripe\PaymentMethod::all([
                    'customer' => $stripeCustomerId,
                    'type' => 'card'
                ]);
                
                $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
                
                if (count($existingMethods->data) <= 1 || empty($defaultPaymentMethod)) {
                    debugLog("Setting payment method as default for customer", [
                        'payment_method_id' => $paymentMethodId,
                        'customer_id' => $stripeCustomerId
                    ]);
                    
                    // Set as default payment method
                    \Stripe\Customer::update($stripeCustomerId, [
                        'invoice_settings' => [
                            'default_payment_method' => $paymentMethodId
                        ]
                    ]);
                    
                    debugLog("Payment method successfully set as default");
                }
            } catch (\Stripe\Exception\CardException $e) {
                // Card errors (like card declined)
                debugLog("Card error when attaching payment method: " . $e->getMessage(), [
                    'payment_method_id' => $paymentMethodId,
                    'error_code' => $e->getStripeCode(),
                    'error_type' => 'card_error'
                ]);
                // Don't fail the payment if it already succeeded, just log the error
            } catch (\Exception $e) {
                // If we get an error that the payment method is already attached, that's fine
                if (strpos($e->getMessage(), 'already been attached') !== false) {
                    debugLog("Payment method is already attached to customer");
                } else {
                    // Log other errors
                    debugLog("Error attaching payment method to customer: " . $e->getMessage(), [
                        'payment_method_id' => $paymentMethodId,
                        'customer_id' => $stripeCustomerId,
                        'error' => $e->getMessage()
                    ]);
                }
                // Don't fail the payment if it already succeeded, just log the error
            }
            
        } catch (\Exception $e) {
            // General error handling for the outer try/catch
            debugLog("General error during payment method handling: " . $e->getMessage(), [
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $stripeCustomerId,
                'error' => $e->getMessage()
            ]);
            // Don't fail the payment if it already succeeded
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        debugLog("Stripe API error: " . $e->getMessage(), [
            'type' => get_class($e),
            'code' => $e->getStripeCode(),
            'status' => $e->getHttpStatus()
        ]);
        throw new Exception('Stripe error: ' . $e->getMessage());
    }
    
    // Check payment intent status
    debugLog("Checking payment intent status: " . $paymentIntent->status);
    
    if ($paymentIntent->status === 'succeeded') {
        // Payment succeeded, add credits to user
        debugLog("Payment succeeded, adding credits to user");
        
        if (!function_exists('addCreditsToUser')) {
            throw new Exception('addCreditsToUser function not found. Check if required functions are properly included.');
        }
        
        $success = addCreditsToUser($userId, $credits);
        
        if (!$success) {
            throw new Exception('Failed to add credits to user account');
        }
        
        // Record payment
        debugLog("Recording payment");
        
        if (!function_exists('recordPayment')) {
            throw new Exception('recordPayment function not found. Check if required functions are properly included.');
        }
        
        $paymentData = [
            'user_id' => $userId,
            'amount' => $amount,
            'credits' => $credits,
            'stripe_payment_id' => $paymentIntent->id,
            'status' => 'completed',
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
            'package_id' => $packageId,
            'is_auto_topup' => 0  // Set to 0 for regular payments (not auto-topup)
        ];
        
        $paymentId = recordPayment($paymentData);
        
        if (!$paymentId) {
            throw new Exception('Failed to record payment');
        }
        
        debugLog("Payment recorded successfully with ID: $paymentId");
        
        // Return success response
        $response = [
            'success' => true,
            'message' => 'Payment successful',
            'payment_method_saved' => true,
            'payment_method_id' => $paymentMethodId,
            'redirect' => 'index.php?page=payment-success&payment_id=' . $paymentId . '&payment_method_saved=1'
        ];
        
        debugLog("Returning success response", $response);
        echo json_encode($response);
        
    } else if ($paymentIntent->status === 'requires_action' && 
               isset($paymentIntent->next_action) && 
               $paymentIntent->next_action->type === 'use_stripe_sdk') {
        // Payment requires additional authentication
        debugLog("Payment requires additional authentication");
        
        $response = [
            'success' => false,
            'requires_action' => true,
            'payment_intent_client_secret' => $paymentIntent->client_secret
        ];
        
        debugLog("Returning requires_action response", $response);
        echo json_encode($response);
        
    } else {
        // Payment failed
        debugLog("Payment failed with status: " . $paymentIntent->status);
        throw new Exception('Payment failed: ' . $paymentIntent->status);
    }
} catch (Exception $e) {
    // Log error
    debugLog("Payment error: " . $e->getMessage(), [
        'exception_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Payment failed: ' . $e->getMessage()
    ]);
} 