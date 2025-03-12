<?php
/**
 * Stripe Webhook Handler
 * 
 * This file processes webhook events from Stripe.
 * It handles payment success, failure, and other events.
 */

// Include core files from outside the public directory
require_once '../../config/init.php';

// Set header to JSON
header('Content-Type: application/json');

// Retrieve the request body and signature header
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify webhook signature
try {
    // Initialize Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Construct event
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sigHeader, STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    logPayment('Webhook Error: Invalid payload', 'error', null, ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    logPayment('Webhook Error: Invalid signature', 'error', null, ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Process the event
try {
    switch ($event->type) {
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($event->data->object);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentIntentFailed($event->data->object);
            break;
            
        case 'charge.refunded':
            handleChargeRefunded($event->data->object);
            break;
            
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event->data->object);
            break;
            
        case 'setup_intent.succeeded':
            handleSetupIntentSucceeded($event->data->object);
            break;
            
        default:
            // Unexpected event type
            logPayment('Unhandled webhook event: ' . $event->type, 'warning');
    }
    
    // Return a 200 response to acknowledge receipt of the event
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'payment_status' => $event->type === 'payment_intent.succeeded' ? 'succeeded' : $event->type,
        'credits_updated' => $event->type === 'payment_intent.succeeded'
    ]);
} catch (Exception $e) {
    // Log error
    logPayment('Webhook Error: ' . $e->getMessage(), 'error', null, [
        'event_type' => $event->type,
        'event_id' => $event->id
    ]);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'payment_status' => 'failed',
        'credits_updated' => false
    ]);
}

/**
 * Handle successful payment intent
 */
function handlePaymentIntentSucceeded($paymentIntent) {
    // Get metadata
    $userId = $paymentIntent->metadata->user_id ?? null;
    $packageId = $paymentIntent->metadata->package_id ?? null;
    $credits = $paymentIntent->metadata->credits ?? null;
    
    if (!$userId || !$packageId || !$credits) {
        logPayment('Missing required metadata in payment intent', 'error', null, [
            'payment_intent_id' => $paymentIntent->id,
            'metadata' => json_encode($paymentIntent->metadata)
        ]);
        throw new Exception('Missing required metadata in payment intent');
    }
    
    // Add credits to user account
    $creditsAdded = addCreditsToUser($userId, $credits);
    
    if (!$creditsAdded) {
        logPayment('Failed to add credits to user account', 'error', $userId, [
            'payment_intent_id' => $paymentIntent->id,
            'credits' => $credits
        ]);
        throw new Exception('Failed to add credits to user account');
    }
    
    // Record the payment
    $paymentId = createPayment(
        $userId,
        $packageId,
        $paymentIntent->amount / 100, // Convert from cents
        $credits,
        $paymentIntent->id // Stripe payment ID
    );
    
    if (!$paymentId) {
        logPayment('Failed to record payment', 'error', $userId, [
            'payment_intent_id' => $paymentIntent->id
        ]);
        // Don't throw here since credits were already added
    }
    
    logPayment('Payment completed successfully', 'info', $userId, [
        'payment_intent_id' => $paymentIntent->id,
        'payment_id' => $paymentId,
        'amount' => $paymentIntent->amount / 100,
        'credits' => $credits
    ]);
}

/**
 * Handle failed payment intent
 */
function handlePaymentIntentFailed($paymentIntent) {
    // Get payment ID from metadata
    $paymentId = $paymentIntent->metadata->payment_id ?? null;
    
    if (!$paymentId) {
        logPayment('Payment failed but no payment_id in metadata', 'error', null, [
            'payment_intent_id' => $paymentIntent->id
        ]);
        return;
    }
    
    // Get payment from database
    $payment = getPaymentById($paymentId);
    
    if (!$payment) {
        logPayment('Payment not found in database', 'error', null, [
            'payment_id' => $paymentId,
            'payment_intent_id' => $paymentIntent->id
        ]);
        return;
    }
    
    // Get error message
    $lastError = $paymentIntent->last_payment_error;
    $errorMessage = $lastError ? $lastError->message : 'Unknown error';
    
    // Mark payment as failed
    $failed = failPayment($paymentId, [
        'transaction_id' => $paymentIntent->id,
        'error_message' => $errorMessage,
        'transaction_data' => json_encode($paymentIntent)
    ]);
    
    if ($failed) {
        logPayment('Payment marked as failed', 'info', $payment['user_id'], [
            'payment_id' => $paymentId,
            'error' => $errorMessage
        ]);
    } else {
        logPayment('Failed to mark payment as failed', 'error', $payment['user_id'], [
            'payment_id' => $paymentId
        ]);
    }
}

/**
 * Handle refunded charge
 */
function handleChargeRefunded($charge) {
    // Get payment intent ID
    $paymentIntentId = $charge->payment_intent;
    
    if (!$paymentIntentId) {
        logPayment('Charge refunded but no payment_intent', 'error', null, [
            'charge_id' => $charge->id
        ]);
        return;
    }
    
    // Find payment by transaction ID
    $payment = getPaymentByTransactionId($paymentIntentId);
    
    if (!$payment) {
        logPayment('Payment not found for refunded charge', 'error', null, [
            'payment_intent_id' => $paymentIntentId
        ]);
        return;
    }
    
    // Process refund
    $refunded = refundPayment($payment['id'], [
        'refund_id' => $charge->refunds->data[0]->id,
        'refund_amount' => $charge->amount_refunded / 100, // Convert from cents
        'refund_data' => json_encode($charge)
    ]);
    
    if ($refunded) {
        // If fully refunded, remove credits from user account
        if ($charge->refunded) {
            $package = getCreditPackageById($payment['package_id']);
            
            if ($package) {
                $creditsRemoved = removeCreditsFromUser($payment['user_id'], $package['credits']);
                
                if ($creditsRemoved) {
                    logPayment('Credits removed due to refund', 'info', $payment['user_id'], [
                        'payment_id' => $payment['id'],
                        'credits' => $package['credits']
                    ]);
                } else {
                    logPayment('Failed to remove credits after refund', 'error', $payment['user_id'], [
                        'payment_id' => $payment['id'],
                        'credits' => $package['credits']
                    ]);
                }
            }
        }
        
        logPayment('Payment refunded', 'info', $payment['user_id'], [
            'payment_id' => $payment['id'],
            'amount' => $charge->amount_refunded / 100
        ]);
    } else {
        logPayment('Failed to process refund', 'error', $payment['user_id'], [
            'payment_id' => $payment['id']
        ]);
    }
}

/**
 * Handle completed checkout session
 */
function handleCheckoutSessionCompleted($session) {
    // Get payment ID from metadata
    $paymentId = $session->metadata->payment_id ?? null;
    
    if (!$paymentId) {
        logPayment('Checkout completed but no payment_id in metadata', 'error', null, [
            'session_id' => $session->id
        ]);
        return;
    }
    
    // Get payment intent
    $paymentIntentId = $session->payment_intent;
    
    if (!$paymentIntentId) {
        logPayment('Checkout completed but no payment_intent', 'error', null, [
            'session_id' => $session->id,
            'payment_id' => $paymentId
        ]);
        return;
    }
    
    // Retrieve payment intent
    try {
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
        
        // Process payment success
        handlePaymentIntentSucceeded($paymentIntent);
    } catch (Exception $e) {
        logPayment('Failed to retrieve payment intent after checkout', 'error', null, [
            'session_id' => $session->id,
            'payment_id' => $paymentId,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Handle successful setup intent
 * 
 * @param \Stripe\SetupIntent $setupIntent
 * @return void
 */
function handleSetupIntentSucceeded($setupIntent) {
    logPayment('Setup intent succeeded', 'info', null, [
        'setup_intent_id' => $setupIntent->id,
        'customer_id' => $setupIntent->customer,
        'payment_method' => $setupIntent->payment_method
    ]);
    
    try {
        // Get the customer from the database
        $sql = "SELECT id FROM users WHERE stripe_customer_id = ?";
        $user = dbFetchRow($sql, [$setupIntent->customer]);
        
        if (!$user) {
            logPayment('Customer not found for setup intent', 'error', null, [
                'setup_intent_id' => $setupIntent->id,
                'customer_id' => $setupIntent->customer
            ]);
            return;
        }
        
        // If this is the customer's first payment method, make it the default
        $paymentMethodsResponse = \Stripe\PaymentMethod::all([
            'customer' => $setupIntent->customer,
            'type' => 'card'
        ]);
        
        if (count($paymentMethodsResponse->data) === 1) {
            // This is the first payment method, make it the default
            \Stripe\Customer::update($setupIntent->customer, [
                'invoice_settings' => [
                    'default_payment_method' => $setupIntent->payment_method
                ]
            ]);
            
            logPayment('Set default payment method', 'info', $user['id'], [
                'customer_id' => $setupIntent->customer,
                'payment_method' => $setupIntent->payment_method
            ]);
        }
        
        // Log user activity
        logUserActivity($user['id'], 'Payment method added', [
            'setup_intent_id' => $setupIntent->id,
            'payment_method' => $setupIntent->payment_method
        ]);
    } catch (Exception $e) {
        logPayment('Error processing setup intent: ' . $e->getMessage(), 'error', null, [
            'setup_intent_id' => $setupIntent->id
        ]);
    }
} 