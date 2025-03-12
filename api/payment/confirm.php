<?php
/**
 * Payment Confirmation API Endpoint
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
debugLog("Payment confirmation API called with method: " . $_SERVER['REQUEST_METHOD']);

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$inputRaw = file_get_contents('php://input');
debugLog("Raw confirmation input received", $inputRaw);

$input = json_decode($inputRaw, true);
debugLog("Decoded confirmation input", $input);

// Validate input
if (!isset($input['payment_intent_id'])) {
    debugLog("Missing payment intent ID");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing payment intent ID']);
    exit;
}

// Get user ID from session
session_start();
debugLog("Session data for confirmation", $_SESSION);

if (!isset($_SESSION['user_id'])) {
    debugLog("User not authenticated for confirmation");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$paymentIntentId = $input['payment_intent_id'];

debugLog("Processing payment confirmation", [
    'user_id' => $userId,
    'payment_intent_id' => $paymentIntentId
]);

// Include required files
try {
    debugLog("Loading required files for confirmation");
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/stripe.php';
    require_once __DIR__ . '/../../models/Payment.php';
    debugLog("Required files for confirmation loaded successfully");
} catch (Exception $e) {
    debugLog("Failed to load required files for confirmation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    exit;
}

try {
    // Initialize Stripe
    debugLog("Initializing Stripe for confirmation");
    if (!function_exists('initStripe')) {
        throw new Exception('initStripe function not found. Check if Stripe configuration is properly included.');
    }
    initStripe();
    
    // Retrieve the payment intent
    debugLog("Retrieving payment intent: " . $paymentIntentId);
    try {
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
        debugLog("Payment intent retrieved", [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        debugLog("Stripe API error during retrieval: " . $e->getMessage(), [
            'type' => get_class($e),
            'code' => $e->getStripeCode(),
            'status' => $e->getHttpStatus()
        ]);
        throw new Exception('Stripe error: ' . $e->getMessage());
    }
    
    // Check if the payment intent belongs to the current user
    debugLog("Checking payment intent metadata", $paymentIntent->metadata->toArray());
    
    if (!isset($paymentIntent->metadata->user_id) || $paymentIntent->metadata->user_id != $userId) {
        debugLog("Payment intent does not belong to the current user", [
            'intent_user_id' => $paymentIntent->metadata->user_id ?? 'not set',
            'current_user_id' => $userId
        ]);
        throw new Exception('Payment intent does not belong to the current user');
    }
    
    // Check payment intent status
    debugLog("Checking payment intent status for confirmation: " . $paymentIntent->status);
    
    if ($paymentIntent->status === 'succeeded') {
        // Get metadata from payment intent
        $metadata = $paymentIntent->metadata->toArray();
        debugLog("Payment intent metadata", $metadata);
        
        $packageId = $metadata['package_id'] ?? null;
        $credits = $metadata['credits'] ?? null;
        $couponCode = $metadata['coupon_code'] ?? null;
        
        if (!$packageId || !$credits) {
            debugLog("Missing required metadata", $metadata);
            throw new Exception('Missing required metadata in payment intent');
        }
        
        // Get package details
        debugLog("Getting package details for confirmation");
        if (!function_exists('getCreditPackageById')) {
            throw new Exception('getCreditPackageById function not found');
        }
        
        $package = getCreditPackageById($packageId);
        if (!$package) {
            debugLog("Invalid package for confirmation", ['package_id' => $packageId]);
            throw new Exception('Invalid package selected');
        }
        
        // Calculate amount (should match the payment intent amount)
        $amount = $paymentIntent->amount / 100; // Convert from cents
        debugLog("Amount from payment intent: $amount");
        
        // Add credits to user
        debugLog("Adding credits to user");
        if (!function_exists('addCreditsToUser')) {
            throw new Exception('addCreditsToUser function not found');
        }
        
        $success = addCreditsToUser($userId, $credits);
        
        if (!$success) {
            debugLog("Failed to add credits to user account");
            throw new Exception('Failed to add credits to user account');
        }
        
        // Record payment
        debugLog("Recording confirmed payment");
        if (!function_exists('recordPayment')) {
            throw new Exception('recordPayment function not found');
        }
        
        $paymentData = [
            'user_id' => $userId,
            'amount' => $amount,
            'credits' => $credits,
            'stripe_payment_id' => $paymentIntentId,
            'status' => 'completed',
            'coupon_code' => $couponCode,
            'package_id' => $packageId
        ];
        
        $paymentId = recordPayment($paymentData);
        
        if (!$paymentId) {
            debugLog("Failed to record confirmed payment");
            throw new Exception('Failed to record payment');
        }
        
        debugLog("Payment confirmation successful, payment ID: $paymentId");
        
        // Return success response with payment status and credits update status
        $response = [
            'success' => true,
            'message' => 'Payment successful',
            'payment_status' => 'succeeded',
            'credits_updated' => true,
            'redirect' => 'index.php?page=payment-success&payment_id=' . $paymentId
        ];
        
        debugLog("Returning success response for confirmation", $response);
        echo json_encode($response);
    } else {
        // Payment failed or still requires action
        debugLog("Payment not completed: " . $paymentIntent->status);
        throw new Exception('Payment not completed: ' . $paymentIntent->status);
    }
} catch (Exception $e) {
    // Log error
    debugLog("Payment confirmation error: " . $e->getMessage(), [
        'exception_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Payment confirmation failed: ' . $e->getMessage()
    ]);
} 