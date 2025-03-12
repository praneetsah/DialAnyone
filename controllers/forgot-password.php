<?php
/**
 * Forgot Password Controller
 */

// Page title
$pageTitle = 'Forgot Password';

// Initialize variables
$forgotPasswordError = null;
$forgotPasswordSuccess = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $forgotPasswordError = 'Invalid form submission, please try again';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $forgotPasswordError = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $forgotPasswordError = 'Please enter a valid email address';
        } else {
            // Check if email exists
            if (isEmailExists($email)) {
                // Get user by email
                $user = getUserByEmail($email);
                
                if ($user) {
                    // Generate reset token
                    $resetToken = bin2hex(random_bytes(32));
                    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token in database
                    $success = storePasswordResetToken($user['id'], $resetToken, $tokenExpiry);
                    
                    if ($success) {
                        // Send password reset email
                        $resetLink = SITE_URL . '/index.php?page=reset-password&token=' . $resetToken;
                        
                        // Email content
                        $subject = 'Password Reset Request';
                        $message = "Hello {$user['name']},\n\n";
                        $message .= "You recently requested to reset your password. Click the link below to reset it:\n\n";
                        $message .= "$resetLink\n\n";
                        $message .= "This link will expire in 1 hour.\n\n";
                        $message .= "If you did not request a password reset, please ignore this email.\n\n";
                        $message .= "Regards,\n";
                        $message .= SITE_NAME . " Team";
                        
                        // Send email
                        $emailSent = sendEmail($user['email'], $subject, $message);
                        
                        if ($emailSent) {
                            // Log the request
                            logMessage("Password reset requested for email: $email", 'info');
                            
                            // Set success message
                            $forgotPasswordSuccess = true;
                        } else {
                            $forgotPasswordError = 'Failed to send password reset email. Please try again later.';
                        }
                    } else {
                        $forgotPasswordError = 'An error occurred. Please try again later.';
                    }
                } else {
                    // User not found, but don't reveal this information
                    $forgotPasswordSuccess = true;
                }
            } else {
                // Email doesn't exist, but don't reveal this information
                $forgotPasswordSuccess = true;
            }
        }
    }
}

// Render view
renderView('auth/forgot-password', [
    'pageTitle' => $pageTitle,
    'forgotPasswordError' => $forgotPasswordError,
    'forgotPasswordSuccess' => $forgotPasswordSuccess
]); 