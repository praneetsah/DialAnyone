<?php
/**
 * Payment Methods Controller
 */

// Enable error logging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Initialize variables
$data = [
    'pageTitle' => 'Manage Payment Methods',
    'error' => null,
    'success' => null,
    'paymentMethods' => [],
    'setupIntent' => null,
    'stripePublishableKey' => '',
    'includeStripeJs' => true,
    'defaultPaymentMethod' => null
];

// Include required files
require_once 'includes/auth.php';
require_once 'config/stripe.php';
require_once 'config/database.php'; // Ensure database config is included

// Get current user's information
$userId = $_SESSION['user_id'];
$user = getUserById($userId);
if (!$user) {
    error_log('Failed to get user data for ID: ' . $userId, 0, 'logs/payment-debug.log');
    $data['error'] = 'Failed to retrieve user information. Please try again.';
    renderView('payment/methods', $data);
    return;
}

// Make sure we have a database connection
global $pdo;
if (!isset($pdo) || $pdo === null) {
    // Try to establish database connection if not already available
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log('Database connection established in payment-methods.php', 0, 'logs/payment-debug.log');
    } catch (\PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage(), 0, 'logs/payment-debug.log');
        $data['error'] = 'Failed to connect to database. Please try again later.';
        renderView('payment/methods', array_merge($data, ['user' => $user]));
        return;
    }
}

// Check for success message in URL
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $data['success'] = "Payment method added successfully";
}

// Initialize Stripe
$initResult = initStripe();
if (!$initResult['success']) {
    // Log the error with more details
    error_log('Stripe initialization failed: ' . $initResult['message'], 0, 'logs/payment-debug.log');
    
    // Check API keys
    error_log('Stripe API keys check: ' . 
              (defined('STRIPE_SECRET_KEY') ? 'Secret key prefix: ' . substr(STRIPE_SECRET_KEY, 0, 7) : 'NOT_DEFINED') . ', ' .
              (defined('STRIPE_PUBLISHABLE_KEY') ? 'Publishable key prefix: ' . substr(STRIPE_PUBLISHABLE_KEY, 0, 7) : 'NOT_DEFINED'),
              0, 'logs/payment-debug.log');
    
    // Check Stripe library
    error_log('Stripe library check: ' . 
              (class_exists('\\Stripe\\Stripe') ? 'LOADED' : 'NOT_LOADED'), 
              0, 'logs/payment-debug.log');
    
    $data['error'] = 'Failed to initialize payment system. Please try again later.';
    renderView('payment/methods', array_merge($data, ['user' => $user]));
    return;
}

// Check if the stripe_customer_id column exists
try {
    $columnCheckSql = "SHOW COLUMNS FROM users LIKE 'stripe_customer_id'";
    $columnStmt = $pdo->prepare($columnCheckSql);
    $columnStmt->execute();
    
    if ($columnStmt->rowCount() === 0) {
        // Column doesn't exist
        error_log('Database structure issue: stripe_customer_id column does not exist in users table', 0, 'logs/payment-debug.log');
        error_log('Please run the SQL script: ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(100) NULL AFTER credits;', 0, 'logs/payment-debug.log');
        
        $data['error'] = 'System configuration issue. Please contact support or run the database update script.';
        $data['debug_message'] = 'Missing required database column: stripe_customer_id';
        
        renderView('payment/methods', array_merge($data, ['user' => $user]));
        return;
    }
} catch (\Exception $e) {
    error_log('Error checking database structure: ' . $e->getMessage(), 0, 'logs/payment-debug.log');
    $data['error'] = 'Error checking system configuration. Please try again later.';
    renderView('payment/methods', array_merge($data, ['user' => $user]));
    return;
}

// Process form actions for setting default payment method or removing payment methods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $data['error'] = "Invalid form submission, please try again";
    } else {
        $action = $_POST['action'];
        
        // Set default payment method
        if ($action === 'set_default_payment_method' && isset($_POST['payment_method_id'])) {
            $paymentMethodId = $_POST['payment_method_id'];
            
            try {
                error_log('Updating default payment method to: ' . $paymentMethodId, 0, 'logs/payment-debug.log');
                
                // Update customer's default payment method
                \Stripe\Customer::update($user['stripe_customer_id'], [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId
                    ]
                ]);
                
                error_log('Default payment method updated successfully', 0, 'logs/payment-debug.log');
                $data['success'] = "Default payment method updated successfully";
            } catch (\Exception $e) {
                error_log('Failed to update default payment method: ' . $e->getMessage(), 0, 'logs/payment-debug.log');
                $data['error'] = "Failed to update default payment method. Please try again.";
            }
        }
        
        // Remove payment method
        if ($action === 'remove_payment_method' && isset($_POST['payment_method_id'])) {
            $paymentMethodId = $_POST['payment_method_id'];
            
            try {
                error_log('Removing payment method: ' . $paymentMethodId, 0, 'logs/payment-debug.log');
                
                // Detach the payment method from the customer
                $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                $paymentMethod->detach();
                
                error_log('Payment method removed successfully', 0, 'logs/payment-debug.log');
                $data['success'] = "Payment method removed successfully";
            } catch (\Exception $e) {
                error_log('Failed to remove payment method: ' . $e->getMessage(), 0, 'logs/payment-debug.log');
                $data['error'] = "Failed to remove payment method. Please try again.";
            }
        }
    }
}

// Main Stripe processing logic
try {
    // Check if user has a Stripe customer ID
    if (empty($user['stripe_customer_id'])) {
        error_log('Creating Stripe customer for user ID: ' . $user['id'], 0, 'logs/payment-debug.log');
        
        // Create a Stripe customer
        $customer = \Stripe\Customer::create([
            'email' => $user['email'],
            'name' => $user['name'],
            'metadata' => [
                'user_id' => $user['id']
            ]
        ]);
        error_log('Stripe customer created: ' . $customer->id, 0, 'logs/payment-debug.log');
        
        // Save the Stripe customer ID to the user record
        $stmt = $pdo->prepare("UPDATE users SET stripe_customer_id = :stripe_customer_id WHERE id = :id");
        $result = $stmt->execute([
            'stripe_customer_id' => $customer->id,
            'id' => $user['id']
        ]);
        
        if (!$result) {
            error_log('Failed to save Stripe customer ID for user: ' . $user['id'], 0, 'logs/payment-debug.log');
            error_log('PDO error info: ' . print_r($stmt->errorInfo(), true), 0, 'logs/payment-debug.log');
            $data['error'] = 'Failed to set up payment methods. Please try again.';
        } else {
            error_log('Stripe customer ID saved for user: ' . $user['id'], 0, 'logs/payment-debug.log');
            $user['stripe_customer_id'] = $customer->id;
        }
    }
    
    // Only proceed with payment methods setup if there's no error
    if (!isset($data['error'])) {
        // Fetch all payment methods for the customer
        error_log('Fetching payment methods for Stripe customer: ' . $user['stripe_customer_id'], 0, 'logs/payment-debug.log');
        $paymentMethods = \Stripe\PaymentMethod::all([
            'customer' => $user['stripe_customer_id'],
            'type' => 'card',
        ]);
        error_log('Payment methods fetched. Count: ' . count($paymentMethods->data), 0, 'logs/payment-debug.log');
        
        // Get customer to determine default payment method
        error_log('Retrieving customer to get default payment method', 0, 'logs/payment-debug.log');
        $customer = \Stripe\Customer::retrieve($user['stripe_customer_id']);
        $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
        error_log('Default payment method: ' . ($defaultPaymentMethod ?? 'None'), 0, 'logs/payment-debug.log');
        
        // Create a setup intent for adding a new payment method
        error_log('Creating setup intent for Stripe customer: ' . $user['stripe_customer_id'], 0, 'logs/payment-debug.log');
        $setupIntent = \Stripe\SetupIntent::create([
            'customer' => $user['stripe_customer_id'],
            'payment_method_types' => ['card'],
        ]);
        error_log('Setup intent created: ' . $setupIntent->id, 0, 'logs/payment-debug.log');
        
        // Add the data to the view data array
        $data['paymentMethods'] = $paymentMethods->data;
        $data['setupIntent'] = $setupIntent;
        $data['stripePublishableKey'] = STRIPE_PUBLISHABLE_KEY;
        $data['includeStripeJs'] = true;
        $data['defaultPaymentMethod'] = $defaultPaymentMethod;
    }
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle Stripe API errors
    $errorMessage = $e->getMessage();
    error_log('Stripe API error: ' . $errorMessage, 0, 'logs/payment-debug.log');
    error_log('Stripe error trace: ' . $e->getTraceAsString(), 0, 'logs/payment-debug.log');
    $data['error'] = 'Failed to set up payment methods. Error: ' . $errorMessage;
} catch (\Exception $e) {
    // Handle other exceptions
    $errorMessage = $e->getMessage();
    error_log('Payment methods error: ' . $errorMessage, 0, 'logs/payment-debug.log');
    error_log('Error trace: ' . $e->getTraceAsString(), 0, 'logs/payment-debug.log');
    $data['error'] = 'An unexpected error occurred. Please try again.';
}

// Render the view with all the data
renderView('payment/methods', array_merge($data, ['user' => $user])); 