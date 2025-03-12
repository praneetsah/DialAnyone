<?php
/**
 * Login Controller
 */

// Page title
$pageTitle = 'Login';

// Page description for SEO
$pageDescription = 'Log in to your Dial Anyone account to make cheap international calls from your browser. Access your account to manage your credits and call history.';

// Process login form submission
$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if form is submitted
    if (isset($_POST['email']) && isset($_POST['password'])) {
        // Get form data
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        
        // Attempt to login
        $user = loginUser($email, $password);
        
        if ($user) {
            // Set user session
            setUserSession($user);
            
            // Log successful login
            logAuth('User logged in successfully', $email, getClientIp());
            
            // Set flash message
            setFlashMessage('success', 'Login successful. Welcome back!');
            
            // Redirect to dashboard
            redirect('index.php?page=dashboard');
        } else {
            // Log failed login
            logAuth('Failed login attempt', $email, getClientIp());
            
            // Set error message
            $loginError = 'Invalid email or password';
        }
    }
}

// Check for redirect from registration
$registrationSuccess = isset($_GET['registered']) && $_GET['registered'] == 1;

// Render view
renderView('auth/login', [
    'pageTitle' => $pageTitle,
    'loginError' => $loginError,
    'registrationSuccess' => $registrationSuccess
]); 