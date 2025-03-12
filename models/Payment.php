<?php
/**
 * Payment Model
 * 
 * Handles payment-related database operations
 */

// Include Stripe configuration if not already included
if (!function_exists('initStripe')) {
    require_once __DIR__ . '/../config/stripe.php';
    
    // Initialize Stripe
    $initResult = initStripe();
    if (!$initResult['success']) {
        error_log('Payment Model: ' . $initResult['message'], 0, 'logs/payment-debug.log');
    }
}

/**
 * Create a new payment
 * 
 * @param int $userId User ID
 * @param int $packageId Package ID
 * @param float $amount Payment amount
 * @param float $credits Credits to add
 * @param string $stripePaymentId Stripe payment ID
 * @param int $couponId Coupon ID (optional)
 * @param float $discountAmount Discount amount (optional)
 * @return int|false Payment ID or false on failure
 */
if (!function_exists('createPayment')) {
    function createPayment($userId, $packageId, $amount, $credits, $stripePaymentId = null, $couponId = null, $discountAmount = 0) {
        // Create payment record
        $paymentId = dbInsert('payments', [
            'user_id' => $userId,
            'package_id' => $packageId,
            'amount' => $amount,
            'credits' => $credits,
            'stripe_payment_id' => $stripePaymentId,
            'coupon_id' => $couponId,
            'discount_amount' => $discountAmount,
            'status' => 'pending'
        ]);
        
        if ($paymentId) {
            logPayment("Payment created", $userId, [
                'package_id' => $packageId,
                'amount' => $amount,
                'credits' => $credits,
                'stripe_payment_id' => $stripePaymentId,
                'coupon_id' => $couponId,
                'discount_amount' => $discountAmount
            ]);
            
            // If coupon was used, log usage
            if ($couponId) {
                dbInsert('coupon_usage', [
                    'coupon_id' => $couponId,
                    'user_id' => $userId,
                    'payment_id' => $paymentId
                ]);
                
                // Update coupon usage count
                dbQuery("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?", [$couponId]);
            }
            
            return $paymentId;
        }
        
        logPayment("Failed to create payment", $userId, [
            'package_id' => $packageId,
            'amount' => $amount
        ]);
        return false;
    }
}

/**
 * Complete a payment
 * 
 * @param int $paymentId Payment ID
 * @return bool Success or failure
 */
if (!function_exists('completePayment')) {
    function completePayment($paymentId) {
        // Get payment
        $sql = "SELECT * FROM payments WHERE id = ?";
        $payment = dbFetchRow($sql, [$paymentId]);
        
        if (!$payment) {
            logPayment("Payment completion failed: Payment not found", null, ['payment_id' => $paymentId]);
            return false;
        }
        
        // Update payment status
        $success = dbUpdate('payments', [
            'status' => 'completed'
        ], 'id = ?', [$paymentId]);
        
        if ($success) {
            // Add credits to user
            addUserCredits($payment['user_id'], $payment['credits']);
            
            logPayment("Payment completed", $payment['user_id'], [
                'payment_id' => $paymentId,
                'amount' => $payment['amount'],
                'credits' => $payment['credits']
            ]);
            
            return true;
        }
        
        logPayment("Payment completion failed: Database error", $payment['user_id'], ['payment_id' => $paymentId]);
        return false;
    }
}

/**
 * Fail a payment
 * 
 * @param int $paymentId Payment ID
 * @param string $reason Failure reason
 * @return bool Success or failure
 */
if (!function_exists('failPayment')) {
    function failPayment($paymentId, $reason = null) {
        // Get payment
        $sql = "SELECT * FROM payments WHERE id = ?";
        $payment = dbFetchRow($sql, [$paymentId]);
        
        if (!$payment) {
            logPayment("Payment failure update failed: Payment not found", null, ['payment_id' => $paymentId]);
            return false;
        }
        
        // Update payment status
        $success = dbUpdate('payments', [
            'status' => 'failed'
        ], 'id = ?', [$paymentId]);
        
        if ($success) {
            logPayment("Payment failed", $payment['user_id'], [
                'payment_id' => $paymentId,
                'amount' => $payment['amount'],
                'reason' => $reason
            ]);
            
            return true;
        }
        
        logPayment("Payment failure update failed: Database error", $payment['user_id'], ['payment_id' => $paymentId]);
        return false;
    }
}

/**
 * Complete a payment by Stripe payment ID
 * 
 * @param string $stripePaymentId Stripe payment ID
 * @return bool Success or failure
 */
function completePaymentByStripeId($stripePaymentId) {
    // Get payment
    $sql = "SELECT * FROM payments WHERE stripe_payment_id = ?";
    $payment = dbFetchRow($sql, [$stripePaymentId]);
    
    if (!$payment) {
        logPayment("Payment completion failed: Payment not found", null, ['stripe_payment_id' => $stripePaymentId]);
        return false;
    }
    
    return completePayment($payment['id']);
}

/**
 * Get payment by ID
 * 
 * @param int $paymentId Payment ID
 * @return array|false Payment data or false if not found
 */
if (!function_exists('getPaymentById')) {
    function getPaymentById($paymentId) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        return dbFetchRow($sql, [$paymentId]);
    }
}

/**
 * Get payment by Stripe payment ID
 * 
 * @param string $stripePaymentId Stripe payment ID
 * @return array|false Payment data or false if not found
 */
function getPaymentByStripeId($stripePaymentId) {
    $sql = "SELECT * FROM payments WHERE stripe_payment_id = ?";
    return dbFetchRow($sql, [$stripePaymentId]);
}

/**
 * Get user's payments
 * 
 * @param int $userId User ID
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Payments data
 */
function getUserPayments($userId, $limit = 100, $offset = 0) {
    $sql = "SELECT payments.*, credit_packages.name as package_name 
            FROM payments 
            LEFT JOIN credit_packages ON payments.package_id = credit_packages.id 
            WHERE payments.user_id = ? 
            ORDER BY payments.created_at DESC LIMIT ? OFFSET ?";
    
    return dbFetchAll($sql, [$userId, $limit, $offset]);
}

/**
 * Get user's payment count
 * 
 * @param int $userId User ID
 * @return int Number of payments
 */
function getUserPaymentCount($userId) {
    $sql = "SELECT COUNT(*) as count FROM payments WHERE user_id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    return $result ? $result['count'] : 0;
}

/**
 * Get all payments
 * 
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Payments data
 */
function getAllPayments($limit = 100, $offset = 0) {
    $sql = "SELECT payments.*, users.name, users.email, credit_packages.name as package_name 
            FROM payments 
            LEFT JOIN users ON payments.user_id = users.id 
            LEFT JOIN credit_packages ON payments.package_id = credit_packages.id 
            ORDER BY payments.created_at DESC LIMIT ? OFFSET ?";
    
    return dbFetchAll($sql, [$limit, $offset]);
}

/**
 * Get payment count
 * 
 * @return int Number of payments
 */
function getPaymentCount() {
    $sql = "SELECT COUNT(*) as count FROM payments";
    $result = dbFetchRow($sql);
    
    return $result ? $result['count'] : 0;
}

/**
 * Get recent payments
 * 
 * @param int $limit Result limit
 * @return array Payments data
 */
function getRecentPayments($limit = 10) {
    $sql = "SELECT payments.*, users.name, users.email, credit_packages.name as package_name 
            FROM payments 
            LEFT JOIN users ON payments.user_id = users.id 
            LEFT JOIN credit_packages ON payments.package_id = credit_packages.id 
            ORDER BY payments.created_at DESC LIMIT ?";
    
    return dbFetchAll($sql, [$limit]);
}

/**
 * Get total revenue
 * 
 * @param string $status Payment status (optional)
 * @return float Total revenue
 */
function getTotalRevenue($status = 'completed') {
    if ($status) {
        $sql = "SELECT SUM(amount) as total FROM payments WHERE status = ?";
        $result = dbFetchRow($sql, [$status]);
    } else {
        $sql = "SELECT SUM(amount) as total FROM payments";
        $result = dbFetchRow($sql);
    }
    
    return $result && $result['total'] ? $result['total'] : 0;
}

/**
 * Get total credits sold
 * 
 * @param string $status Payment status (optional)
 * @return float Total credits
 */
function getTotalCreditsSold($status = 'completed') {
    if ($status) {
        $sql = "SELECT SUM(credits) as total FROM payments WHERE status = ?";
        $result = dbFetchRow($sql, [$status]);
    } else {
        $sql = "SELECT SUM(credits) as total FROM payments";
        $result = dbFetchRow($sql);
    }
    
    return $result && $result['total'] ? $result['total'] : 0;
}

/**
 * Get revenue by package
 * 
 * @return array Revenue by package
 */
function getRevenueByPackage() {
    $sql = "SELECT credit_packages.name, SUM(payments.amount) as total 
            FROM payments 
            LEFT JOIN credit_packages ON payments.package_id = credit_packages.id 
            WHERE payments.status = 'completed' 
            GROUP BY payments.package_id";
    
    return dbFetchAll($sql);
}

/**
 * Get credit packages
 * 
 * @param bool $activeOnly Only get active packages
 * @return array Credit packages
 */
function getCreditPackages($activeOnly = true) {
    if ($activeOnly) {
        $sql = "SELECT * FROM credit_packages WHERE is_active = 1 ORDER BY price ASC";
        return dbFetchAll($sql);
    } else {
        $sql = "SELECT * FROM credit_packages ORDER BY price ASC";
        return dbFetchAll($sql);
    }
}

/**
 * Get credit package by ID
 * 
 * @param int $packageId Package ID
 * @return array|false Package data or false if not found
 */
function getCreditPackageById($packageId) {
    $sql = "SELECT * FROM credit_packages WHERE id = ?";
    return dbFetchRow($sql, [$packageId]);
}

/**
 * Update payment record
 * 
 * @param int $paymentId Payment ID
 * @param array $paymentData Payment data to update
 * @return bool Success or failure
 */
if (!function_exists('updatePayment')) {
    function updatePayment($paymentId, $paymentData) {
        // ... existing code ...
    }
}

/**
 * Complete payment and update record
 * 
 * @param int $paymentId Payment ID
 * @param array $paymentData Additional payment data
 * @return bool Success or failure
 */
if (!function_exists('completePayment')) {
    function completePayment($paymentId, $paymentData = []) {
        // ... existing code ...
    }
}

/**
 * Fail payment and update record
 * 
 * @param int $paymentId Payment ID
 * @param array $paymentData Additional payment data
 * @return bool Success or failure
 */
if (!function_exists('failPayment')) {
    function failPayment($paymentId, $paymentData = []) {
        // ... existing code ...
    }
}

/**
 * Record a payment
 * 
 * @param array $paymentData Payment data
 * @return int|false Payment ID or false on failure
 */
function recordPayment($paymentData) {
    if (!isset($paymentData['user_id']) || !isset($paymentData['amount']) || !isset($paymentData['credits'])) {
        return false;
    }
    
    // Create payment record
    $data = [
        'user_id' => $paymentData['user_id'],
        'amount' => $paymentData['amount'],
        'credits' => $paymentData['credits'],
        'stripe_payment_id' => $paymentData['stripe_payment_id'] ?? null,
        'status' => $paymentData['status'] ?? 'completed',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add optional fields if present
    if (isset($paymentData['package_id'])) {
        $data['package_id'] = $paymentData['package_id'];
    }
    
    if (isset($paymentData['coupon_code']) && !empty($paymentData['coupon_code'])) {
        // Use the existing getCouponByCode function to get coupon info
        if (function_exists('getCouponByCode')) {
            $coupon = getCouponByCode($paymentData['coupon_code']);
            if ($coupon && isset($coupon['id'])) {
                $data['coupon_id'] = $coupon['id'];
            }
        }
    }
    
    if (isset($paymentData['discount_amount'])) {
        $data['discount_amount'] = $paymentData['discount_amount'];
    }
    
    // Add is_auto_topup flag if present
    if (isset($paymentData['is_auto_topup'])) {
        $data['is_auto_topup'] = $paymentData['is_auto_topup'];
    }
    
    // Insert into database
    $paymentId = dbInsert('payments', $data);
    
    if ($paymentId) {
        // Log payment
        logPayment("Payment recorded: $paymentId", $paymentData['user_id'], [
            'amount' => $paymentData['amount'],
            'credits' => $paymentData['credits'],
            'stripe_payment_id' => $paymentData['stripe_payment_id'] ?? null,
            'is_auto_topup' => $paymentData['is_auto_topup'] ?? 0
        ]);
        
        return $paymentId;
    }
    
    // Log failure
    logPayment("Failed to record payment", $paymentData['user_id'], [
        'amount' => $paymentData['amount'],
        'credits' => $paymentData['credits'],
        'stripe_payment_id' => $paymentData['stripe_payment_id'] ?? null,
        'is_auto_topup' => $paymentData['is_auto_topup'] ?? 0
    ]);
    
    return false;
}

/**
 * Add credits to user
 * 
 * @param int $userId User ID
 * @param float $credits Credits to add
 * @return bool Success or failure
 */
function addCreditsToUser($userId, $credits) {
    // Get current credits
    $user = getUserById($userId);
    if (!$user) {
        return false;
    }
    
    $currentCredits = $user['credits'] ?? 0;
    $newCredits = $currentCredits + $credits;
    
    // Update user credits
    $success = dbUpdate('users', [
        'credits' => $newCredits
    ], 'id = ?', [$userId]);
    
    if ($success) {
        // Update session if this is the current user
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['user_credits'] = $newCredits;
        }
        
        // Log credits added
        logPayment("Credits added to user: $credits", $userId, [
            'previous_balance' => $currentCredits,
            'new_balance' => $newCredits
        ]);
        
        return true;
    }
    
    // Log failure
    logPayment("Failed to add credits to user", $userId, [
        'credits' => $credits
    ]);
    
    return false;
}

/**
 * Checks if user has auto-topup enabled and processes it if needed
 * 
 * @param int $userId User ID
 * @return bool Success or failure
 */
function checkAndProcessAutoTopup($userId) {
    global $pdo;
    
    try {
        // Get user details
        $user = getUserById($userId);
        if (!$user) {
            error_log("Auto-topup: User not found: $userId", 0, 'logs/payment-debug.log');
            return false;
        }
        
        // Check if auto-topup is enabled
        if (!isset($user['auto_topup']) || $user['auto_topup'] != 1) {
            error_log("Auto-topup: Not enabled for user: $userId", 0, 'logs/payment-debug.log');
            return false;
        }
        
        // Get the package ID for topup
        $packageId = $user['topup_package'] ?? 1; // Default to package 1 if not set
        
        // Check if user has a Stripe customer ID
        if (empty($user['stripe_customer_id'])) {
            error_log("Auto-topup: No Stripe customer ID for user: $userId", 0, 'logs/payment-debug.log');
            return false;
        }
        
        // Check if user has a default payment method
        try {
            $customer = \Stripe\Customer::retrieve($user['stripe_customer_id']);
            $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
            
            if (!$defaultPaymentMethod) {
                error_log("Auto-topup: No default payment method for user: $userId", 0, 'logs/payment-debug.log');
                return false;
            }
            
            // Get package details
            $package = getCreditPackageById($packageId);
            if (!$package) {
                error_log("Auto-topup: Invalid package ID: $packageId", 0, 'logs/payment-debug.log');
                return false;
            }
            
            $amount = $package['price'];
            $credits = $package['credits'];
            
            error_log("Auto-topup: Processing for user $userId, package $packageId, amount $amount, credits $credits", 0, 'logs/payment-debug.log');
            
            // Create a payment intent
            $intent = \Stripe\PaymentIntent::create([
                'amount' => round($amount * 100), // Convert to cents
                'currency' => 'usd',
                'customer' => $user['stripe_customer_id'],
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
            
            error_log("Auto-topup: Created payment intent: " . $intent->id, 0, 'logs/payment-debug.log');
            
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
                        error_log("Auto-topup: Successfully added $credits credits to user $userId", 0, 'logs/payment-debug.log');
                        return true;
                    } else {
                        error_log("Auto-topup: Failed to add credits to user $userId", 0, 'logs/payment-debug.log');
                    }
                } else {
                    error_log("Auto-topup: Failed to record payment for user $userId", 0, 'logs/payment-debug.log');
                }
            } else {
                error_log("Auto-topup: Payment intent not succeeded: " . $intent->status, 0, 'logs/payment-debug.log');
            }
        } catch (\Exception $e) {
            error_log("Auto-topup error: " . $e->getMessage(), 0, 'logs/payment-debug.log');
        }
        
        return false;
    } catch (\Exception $e) {
        error_log("Auto-topup processing error: " . $e->getMessage(), 0, 'logs/payment-debug.log');
        return false;
    }
} 