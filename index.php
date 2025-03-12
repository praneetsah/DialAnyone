<?php
/**
 * Main Application Entry Point
 */

// Start session
session_start();

// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-error-log.txt');

// Load configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/twilio.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/mail.php';

// Load includes
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/logger.php';

// Load models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Call.php';
require_once __DIR__ . '/models/Payment.php';
require_once __DIR__ . '/models/Coupon.php';

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Default page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Check if API request
if (strpos($page, 'api/') === 0) {
    // API routes
    $apiRoute = substr($page, 4); // Remove 'api/' prefix
    
    // Include API controller
    $apiFile = __DIR__ . '/api/' . $apiRoute . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit;
    } else {
        // API endpoint not found
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

// Authentication check for protected pages
$publicPages = ['home', 'login', 'register', 'verify', 'forgot-password', 'reset-password', 'terms', 'privacy', 'calling-rates'];

if (!in_array($page, $publicPages) && !isAuthenticated()) {
    // Redirect to login
    setFlashMessage('error', 'Please log in to access this page.');
    redirect('index.php?page=login');
}

// Admin check for admin pages
if (strpos($page, 'admin/') === 0 && !isAdmin()) {
    // Redirect to dashboard
    setFlashMessage('error', 'You do not have permission to access that page.');
    redirect('index.php?page=dashboard');
}

// Check if verification is required
if (isAuthenticated() && needsVerification()) {
    // Redirect to verification page
    setFlashMessage('warning', 'Please verify your email address to access this feature.');
    redirect('index.php?page=verify');
}

// Include page controller
$controllerFile = __DIR__ . '/controllers/' . $page . '.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
} else {
    // Default to dashboard for authenticated users
    if (isAuthenticated()) {
        require_once __DIR__ . '/controllers/dashboard.php';
    } else {
        // Default to home for non-authenticated users
        require_once __DIR__ . '/controllers/home.php';
    }
}

/**
 * Render view
 * 
 * @param string $view View name
 * @param array $data View data
 * @return void
 */
function renderView($view, $data = []) {
    // Extract data for use in view
    extract($data);
    
    // Include header
    require_once __DIR__ . '/views/partials/header.php';
    
    // Include view
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        echo '<div class="container mt-5"><div class="alert alert-danger">View not found: ' . $view . '</div></div>';
    }
    
    // Include footer
    require_once __DIR__ . '/views/partials/footer.php';
}

/**
 * Render JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 * @return void
 */
function renderJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
} 