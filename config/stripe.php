<?php
/**
 * Stripe Configuration
 * 
 * This file contains Stripe API credentials and helper functions
 */

// Stripe API credentials - Change these to your actual Stripe credentials
define('STRIPE_SECRET_KEY', 'your_stripe_secret_key');
define('STRIPE_PUBLISHABLE_KEY', 'your_stripe_publishable_key');
define('STRIPE_WEBHOOK_SECRET', 'your_stripe_webhook_secret');

// Initialize Stripe
function initStripe() {
    $result = [
        'success' => false,
        'message' => 'Unknown error'
    ];
    
    // Check if Stripe library is installed
    if (!class_exists('\\Stripe\\Stripe')) {
        $errorMsg = 'Stripe library not found. Please install the Stripe PHP SDK.';
        error_log('ERROR: ' . $errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
    
    // Check if API key is configured
    if (empty(STRIPE_SECRET_KEY) || STRIPE_SECRET_KEY === 'your_stripe_secret_key') {
        $errorMsg = 'Stripe API keys not configured. Please set your Stripe API keys in config/stripe.php.';
        error_log('ERROR: ' . $errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
    
    try {
        // Set API key
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        // Verify the API key by making a simple request
        \Stripe\Account::retrieve();
        
        $result['success'] = true;
        $result['message'] = 'Stripe initialized successfully';
        return $result;
    } catch (\Exception $e) {
        // Log error
        $errorMsg = 'Stripe initialization error: ' . $e->getMessage();
        error_log($errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
}

// Create a payment intent
function createPaymentIntent($amount, $currency = 'usd', $metadata = []) {
    $result = [
        'success' => false,
        'message' => 'Unknown error'
    ];
    
    // Initialize Stripe
    $init = initStripe();
    if (!$init['success']) {
        return $init;
    }
    
    try {
        // Create a payment intent
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
        
        $result['success'] = true;
        $result['message'] = 'Payment intent created';
        $result['data'] = [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'amount' => $intent->amount / 100, // Convert back to dollars
            'currency' => $intent->currency
        ];
        
        return $result;
    } catch (\Exception $e) {
        // Log error
        $errorMsg = 'Payment intent creation error: ' . $e->getMessage();
        error_log($errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
}

// Create a checkout session
function createCheckoutSession($amount, $name, $successUrl, $cancelUrl, $metadata = []) {
    $result = [
        'success' => false,
        'message' => 'Unknown error'
    ];
    
    // Initialize Stripe
    $init = initStripe();
    if (!$init['success']) {
        return $init;
    }
    
    try {
        // Create a checkout session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => $amount * 100, // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);
        
        $result['success'] = true;
        $result['message'] = 'Checkout session created';
        $result['data'] = [
            'id' => $session->id,
            'url' => $session->url
        ];
        
        return $result;
    } catch (\Exception $e) {
        // Log error
        $errorMsg = 'Checkout session creation error: ' . $e->getMessage();
        error_log($errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
}

// Verify webhook signature
function verifyWebhookSignature($payload, $sigHeader) {
    $result = [
        'success' => false,
        'message' => 'Unknown error'
    ];
    
    // Check if Stripe library is installed
    if (!class_exists('\\Stripe\\Webhook')) {
        $errorMsg = 'Stripe library not found. Please install the Stripe PHP SDK.';
        error_log('ERROR: ' . $errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
    
    try {
        // Verify the webhook signature
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, STRIPE_WEBHOOK_SECRET
        );
        
        $result['success'] = true;
        $result['message'] = 'Webhook signature verified';
        $result['data'] = $event;
        
        return $result;
    } catch (\UnexpectedValueException $e) {
        // Invalid payload
        $errorMsg = 'Invalid payload: ' . $e->getMessage();
        error_log($errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        $errorMsg = 'Invalid signature: ' . $e->getMessage();
        error_log($errorMsg, 0, __DIR__ . '/../logs/payment-debug.log');
        $result['message'] = $errorMsg;
        return $result;
    }
}

// Apply coupon discount to amount
function applyCouponDiscount($amount, $coupon) {
    // Simple coupon implementation
    // In a real app, you would store coupons in the database
    $validCoupons = [
        'WELCOME10' => ['percent' => 10, 'valid' => true],
        'WELCOME20' => ['percent' => 20, 'valid' => true],
        'SALE30' => ['percent' => 30, 'valid' => true]
    ];
    
    // Check if coupon is valid
    if (!isset($validCoupons[$coupon]) || !$validCoupons[$coupon]['valid']) {
        return [
            'success' => false,
            'message' => 'Invalid coupon code',
            'amount' => $amount
        ];
    }
    
    // Calculate discount
    $percent = $validCoupons[$coupon]['percent'];
    $discount = $amount * ($percent / 100);
    $discountedAmount = $amount - $discount;
    
    return [
        'success' => true,
        'message' => "Coupon applied: $percent% off",
        'amount' => $discountedAmount,
        'original_amount' => $amount,
        'discount' => $discount,
        'discount_percent' => $percent
    ];
} 