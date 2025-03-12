<?php
/**
 * Register Controller
 */

// Page title
$pageTitle = 'Register';

// Page description for SEO
$pageDescription = 'Create your Dial Anyone account and start making cheap international calls directly from your browser. Sign up today and get free welcome credits!';

// Process registration form submission
$registrationErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $registrationErrors['csrf'] = 'Invalid form submission, please try again';
    } else {
        // Get form data
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $termsAgreed = isset($_POST['terms']);
        
        // Validation
        if (empty($name)) {
            $registrationErrors['name'] = 'Name is required';
        }
        
        if (empty($email)) {
            $registrationErrors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $registrationErrors['email'] = 'Please enter a valid email address';
        } elseif (isEmailExists($email)) {
            $registrationErrors['email'] = 'Email is already registered';
        }
        
        if (empty($phone)) {
            $registrationErrors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            $registrationErrors['phone'] = 'Please enter a valid phone number';
        } elseif (isPhoneExists($phone)) {
            $registrationErrors['phone'] = 'Phone number is already registered';
        }
        
        if (empty($password)) {
            $registrationErrors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $registrationErrors['password'] = 'Password must be at least 8 characters long';
        }
        
        if ($password !== $confirmPassword) {
            $registrationErrors['confirm_password'] = 'Passwords do not match';
        }
        
        if (!$termsAgreed) {
            $registrationErrors['terms'] = 'You must agree to the Terms of Service and Privacy Policy';
        }
        
        // If no errors, create user
        if (empty($registrationErrors)) {
            $userId = createUser([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'is_admin' => 0,
                'credits' => WELCOME_CREDITS, // Welcome credits from config
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($userId) {
                // Log user creation
                logAuth('User registered successfully', $email, getClientIp());
                
                // Redirect to login page with success message
                redirect('index.php?page=login&registered=1');
            } else {
                $registrationErrors['general'] = 'Registration failed. Please try again later.';
            }
        }
    }
}

// Render view
renderView('auth/register', [
    'pageTitle' => $pageTitle,
    'registrationErrors' => $registrationErrors
]); 