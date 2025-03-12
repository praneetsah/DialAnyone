<?php
/**
 * Payment Success Controller
 */

// Page title
$pageTitle = 'Payment Successful';

// Get payment ID from URL
$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
$paymentMethodSaved = isset($_GET['payment_method_saved']) && $_GET['payment_method_saved'] == 1;

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get payment details
$payment = null;
if ($paymentId) {
    $payment = getPaymentById($paymentId);
    
    // Verify payment belongs to user
    if (!$payment || $payment['user_id'] != $userId) {
        $payment = null;
    }
}

// Get user data
$user = getUserById($userId);

// Prepare view data
$viewData = [
    'pageTitle' => $pageTitle,
    'payment' => $payment,
    'user' => $user
];

// Add success message about saved payment method if applicable
if ($paymentMethodSaved) {
    $viewData['payment_method_saved'] = true;
    $viewData['payment_method_message'] = 'Your card has been saved for future payments. You can manage your payment methods on the <a href="index.php?page=payment-methods">Payment Methods</a> page.';
}

// Render view
renderView('payment/success', $viewData); 