<?php
/**
 * Email Verification Controller
 * 
 * This controller handles email verification
 */

// Set page title
$pageTitle = 'Verify Your Email';

// Redirect if not logged in
if (!isAuthenticated()) {
    redirect('index.php?page=login');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if (!$user) {
    redirect('index.php?page=login');
}

// Check if already verified
if (isset($_SESSION['is_email_verified']) && $_SESSION['is_email_verified']) {
    redirect('index.php?page=dashboard');
}

// Check if actually verified in database (session might be out of sync)
$isActuallyVerified = isEmailVerified($userId);

// Initialize variables
$verificationError = '';
$resendSuccess = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $verificationError = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'verify') {
            // Verify code
            $code = $_POST['verification_code'] ?? '';
            
            if (empty($code)) {
                $verificationError = 'Please enter the verification code.';
            } else {
                $success = verifyEmail($userId, $code);
                
                if ($success) {
                    // Set session
                    $_SESSION['is_email_verified'] = 1;
                    
                    // Set success message
                    setFlashMessage('success', 'Your email has been verified successfully!');
                    
                    // Redirect to dashboard
                    redirect('index.php?page=dashboard');
                } else {
                    $verificationError = 'Invalid verification code. Please try again.';
                }
            }
        } elseif ($action === 'resend') {
            // Resend verification code
            $success = resetEmailVerificationCode($userId);
            
            if ($success) {
                $resendSuccess = true;
                // Log resend
                logMessage("Verification code resent to user: {$user['email']}", 'info');
            } else {
                $verificationError = 'Failed to send verification code. Please try again later.';
            }
        } elseif ($action === 'force_update' && $isActuallyVerified) {
            // Force update session
            $_SESSION['is_email_verified'] = 1;
            
            // Set success message
            setFlashMessage('success', 'Your verification status has been updated. You can now use all features.');
            
            // Redirect to dashboard
            redirect('index.php?page=dashboard');
        }
    }
}

// Render view
renderView('auth/verify', [
    'pageTitle' => $pageTitle,
    'user' => $user,
    'verificationError' => $verificationError,
    'resendSuccess' => $resendSuccess,
    'isActuallyVerified' => $isActuallyVerified
]); 