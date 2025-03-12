<?php
/**
 * Auto Topup Check Script
 * 
 * This script is meant to be run via cron to check all users for low balances
 * and process auto-topup for those who have it enabled.
 * 
 * Recommended cron schedule: Every 15-30 minutes
 * Example: 
 */

// Disable direct access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access denied. This script can only be run from the command line.";
    exit;
}

// Set up error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/cron-error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Ensure auto-topup log file exists
$logFile = __DIR__ . '/../logs/auto-topup.log';
if (!file_exists($logFile)) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Auto-topup log initialized\n");
}

// Function to log messages
function logAutoTopup($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "{$timestamp} - {$message}\n", FILE_APPEND);
    echo "{$timestamp} - {$message}\n";
}

// Start logging
logAutoTopup("Auto-topup check started");

// Include required files
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/stripe.php';
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/logger.php';
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/Payment.php';
    require_once __DIR__ . '/../models/Coupon.php';
    
    logAutoTopup("Required files loaded successfully");
} catch (Exception $e) {
    logAutoTopup("Error loading required files: " . $e->getMessage());
    exit(1);
}

// Initialize Stripe
try {
    $initResult = initStripe();
    if (!$initResult['success']) {
        logAutoTopup("Stripe initialization failed: {$initResult['message']}");
        exit(1);
    }
    logAutoTopup("Stripe initialized successfully");
} catch (Exception $e) {
    logAutoTopup("Stripe initialization error: " . $e->getMessage());
    exit(1);
}

// Connect to database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logAutoTopup("Database connection established");
} catch (PDOException $e) {
    logAutoTopup("Database connection failed: " . $e->getMessage());
    exit(1);
}

// Log start of process
$startTime = microtime(true);

// Get the minimum credits threshold from settings
try {
    $minCredits = getSetting('min_credits_for_topup', 100);
    logAutoTopup("Using minimum credits threshold: $minCredits");
} catch (Exception $e) {
    logAutoTopup("Error getting minimum credits threshold: " . $e->getMessage());
    $minCredits = 100; // Use default value if there's an error
    logAutoTopup("Using default minimum credits threshold: $minCredits");
}

// Find users with low balance and auto-topup enabled
$sql = "SELECT id, name, email, credits, stripe_customer_id, topup_package FROM users 
        WHERE auto_topup = 1 
        AND credits < :min_credits
        AND stripe_customer_id IS NOT NULL
        AND topup_package IS NOT NULL";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['min_credits' => $minCredits]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalUsers = count($users);
    $processedCount = 0;
    $successCount = 0;
    
    logAutoTopup("Found $totalUsers users with low balance and auto-topup enabled");
    
    // Process each user
    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'];
        $currentBalance = $user['credits'];
        $stripeCustomerId = $user['stripe_customer_id'];
        $packageId = $user['topup_package'];
        
        logAutoTopup("Processing user ID: $userId, Name: $userName, Current balance: $currentBalance");
        
        // Get package details
        try {
            $package = getCreditPackageById($packageId);
            if (!$package) {
                logAutoTopup("Invalid package ID: $packageId for user ID: $userId");
                continue;
            }
            
            $amount = $package['price'];
            $credits = $package['credits'];
            
            logAutoTopup("Package details - ID: $packageId, Amount: $amount, Credits: $credits");
        } catch (Exception $e) {
            logAutoTopup("Error getting package details for user ID: $userId: " . $e->getMessage());
            continue;
        }
        
        // Process auto-topup
        try {
            // Get customer to check for payment method
            $customer = \Stripe\Customer::retrieve($stripeCustomerId);
            $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
            
            if (!$defaultPaymentMethod) {
                logAutoTopup("No default payment method for user ID: $userId");
                continue;
            }
            
            // Create payment intent
            $intent = \Stripe\PaymentIntent::create([
                'amount' => round($amount * 100), // Convert to cents
                'currency' => 'usd',
                'customer' => $stripeCustomerId,
                'payment_method' => $defaultPaymentMethod,
                'confirm' => true, // Confirm immediately
                'off_session' => true, // This is an automatic payment
                'metadata' => [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'credits' => $credits,
                    'is_auto_topup' => 'true'
                ]
            ]);
            
            logAutoTopup("Created payment intent: " . $intent->id . " for user ID: $userId");
            
            // Check payment status
            if ($intent->status === 'succeeded') {
                // Record payment
                $paymentData = [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'amount' => $amount,
                    'credits' => $credits,
                    'stripe_payment_id' => $intent->id,
                    'status' => 'completed',
                    'is_auto_topup' => 1
                ];
                
                $paymentId = recordPayment($paymentData);
                
                if ($paymentId) {
                    // Add credits to user
                    $success = addCreditsToUser($userId, $credits);
                    
                    if ($success) {
                        $successCount++;
                        logAutoTopup("Auto-topup successful for user ID: $userId - Added $credits credits");
                        
                        // Send notification email
                        if (function_exists('sendPaymentNotification')) {
                            try {
                                sendPaymentNotification($userId, $amount, $credits, true);
                                logAutoTopup("Payment notification sent to user ID: $userId");
                            } catch (Exception $e) {
                                logAutoTopup("Error sending payment notification to user ID: $userId: " . $e->getMessage());
                            }
                        }
                    } else {
                        logAutoTopup("Failed to add credits to user ID: $userId");
                    }
                } else {
                    logAutoTopup("Failed to record payment for user ID: $userId");
                }
            } else {
                logAutoTopup("Payment intent not succeeded: " . $intent->status . " for user ID: $userId");
            }
        } catch (\Exception $e) {
            logAutoTopup("Error processing auto-topup for user ID: $userId: " . $e->getMessage());
        }
        
        $processedCount++;
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    logAutoTopup("Auto-topup check completed in $duration seconds");
    logAutoTopup("Processed: $processedCount, Successful: $successCount");
    logAutoTopup("-----------------------------------------------------");
    
} catch (PDOException $e) {
    logAutoTopup("Database error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    logAutoTopup("General error: " . $e->getMessage());
    exit(1);
} 