<?php
/**
 * Credits Controller
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log access to this page
file_put_contents(__DIR__ . '/../logs/credits-debug.log', date('Y-m-d H:i:s') . " - Credits page accessed by user ID: " . ($_SESSION['user_id'] ?? 'unknown') . "\n", FILE_APPEND);

// Page title
$pageTitle = 'Buy Credits';

// Include Stripe JS
$includeStripeJs = true;

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Log user data
file_put_contents(__DIR__ . '/../logs/credits-debug.log', date('Y-m-d H:i:s') . " - User data: " . json_encode($user) . "\n", FILE_APPEND);

// Get credit packages
$creditPackages = getCreditPackages();

// Log credit packages
file_put_contents(__DIR__ . '/../logs/credits-debug.log', date('Y-m-d H:i:s') . " - Credit packages: " . json_encode($creditPackages) . "\n", FILE_APPEND);

// Get Stripe public key
$stripePublicKey = STRIPE_PUBLISHABLE_KEY;

// Process coupon code validation
$couponCode = null;
$couponDiscount = null;
$couponError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'validate_coupon') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $couponError = 'Invalid form submission';
    } else {
        $couponCode = sanitizeInput($_POST['coupon_code'] ?? '');
        
        if (empty($couponCode)) {
            $couponError = 'Please enter a coupon code';
        } else {
            // Validate coupon
            $coupon = getCouponByCode($couponCode);
            
            if (!$coupon) {
                $couponError = 'Invalid coupon code';
            } elseif (!isCouponValid($coupon)) {
                $couponError = 'This coupon has expired or is no longer valid';
            } else {
                // Get discount amount
                $couponDiscount = [
                    'code' => $coupon['code'],
                    'type' => $coupon['discount_type'],
                    'value' => $coupon['discount_value']
                ];
                
                // Log coupon application
                logPayment('Coupon applied: ' . $coupon['code'], 'info', $userId, ['coupon' => $coupon['code']]);
            }
        }
    }
}

// Render view
renderView('payment/credits', [
    'pageTitle' => $pageTitle,
    'includeStripeJs' => $includeStripeJs,
    'stripePublicKey' => $stripePublicKey,
    'creditPackages' => $creditPackages,
    'user' => $user,
    'couponCode' => $couponCode,
    'couponDiscount' => $couponDiscount,
    'couponError' => $couponError
]); 