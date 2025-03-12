<?php
/**
 * Logout Controller
 */

// Get user email for logging before we logout
$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Call logout function to clear session
logoutUser();

// Log logout action
logAuth('User logged out', $userEmail, getClientIp());

// Set flash message
setFlashMessage('success', 'You have been successfully logged out.');

// Redirect to home page
redirect('index.php?page=home'); 