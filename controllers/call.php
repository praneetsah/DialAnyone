<?php
/**
 * Call Controller
 */

// Page title
$pageTitle = 'Make a Call';

// Include Twilio JS
$includeTwilioJs = true;

// Make sure Twilio config is loaded
require_once 'config/twilio.php';

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Check if user has enough credits
$hasCredits = $user['credits'] > 0;

// Generate Twilio token for the client
$twilioToken = generateTwilioToken($userId);

// Get recent calls for quick dial
$recentCalls = getUserCalls($userId, 5, 0);

// Render view
renderView('call/index', [
    'pageTitle' => $pageTitle,
    'includeTwilioJs' => $includeTwilioJs,
    'twilioToken' => $twilioToken,
    'hasCredits' => $hasCredits,
    'userCredits' => $user['credits'],
    'recentCalls' => $recentCalls,
    'userId' => $userId
]); 