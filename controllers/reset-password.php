<?php
/**
 * Reset Password Controller
 */

// Page title
$pageTitle = 'Reset Password';

// Initialize variables
$resetPasswordError = null;
$resetPasswordSuccess = false;
$validToken = false;
$token = sanitizeInput($_GET['token'] ?? '');

// Check if token exists and is valid
if (!empty($token)) {
    $tokenData = getPasswordResetToken($token);
    
    if ($tokenData && !isTokenExpired($tokenData['expires_at'])) {
        $validToken = true;
    } else {
        $resetPasswordError = 'Invalid or expired password reset link. Please request a new one.';
    }
} else {
    $resetPasswordError = 'Password reset token is missing. Please request a new password reset link.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $resetPasswordError = 'Invalid form submission, please try again';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $resetPasswordError = 'Please enter a new password';
        } elseif (strlen($password) < 8) {
            $resetPasswordError = 'Password must be at least 8 characters long';
        } elseif ($password !== $confirmPassword) {
            $resetPasswordError = 'Passwords do not match';
        } else {
            // Update password
            $userId = $tokenData['user_id'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $success = updateUserPassword($userId, $hashedPassword);
            
            if ($success) {
                // Invalidate token after use
                invalidatePasswordResetToken($token);
                
                // Log password reset
                logMessage("Password reset completed for user ID: $userId", 'info');
                
                // Set success message
                $resetPasswordSuccess = true;
                
                // Clear token validation status
                $validToken = false;
            } else {
                $resetPasswordError = 'Failed to update password. Please try again later.';
            }
        }
    }
}

// Render view
renderView('auth/reset-password', [
    'pageTitle' => $pageTitle,
    'resetPasswordError' => $resetPasswordError,
    'resetPasswordSuccess' => $resetPasswordSuccess,
    'validToken' => $validToken,
    'token' => $token
]); 