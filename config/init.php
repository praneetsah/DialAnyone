<?php
/**
 * Initialization File
 * 
 * This file is included by the API endpoints to load all necessary dependencies
 */

// Set error reporting for API files
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api-error-log.txt');

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/twilio.php';
require_once __DIR__ . '/stripe.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/mail.php';

// Load include files
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

// Load models
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Call.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/CreditPackage.php';
require_once __DIR__ . '/../models/Coupon.php';

// Set timezone
date_default_timezone_set('UTC');

// Start or resume session
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', ($_SERVER['HTTPS'] ?? '') === 'on' ? 1 : 0);
    
    session_start();
}

// Verify if request is from Twilio
function isTwilioRequest() {
    // Get the requesting IP address
    $ip = getClientIp();
    
    // Check if the IP matches Twilio's IP ranges
    // For a production system, you should validate against Twilio's IP ranges: https://www.twilio.com/docs/api/security#validating-requests
    // For simplicity, we'll just check if the request contains a Twilio signature header
    return isset($_SERVER['HTTP_X_TWILIO_SIGNATURE']);
}

// Get client IP address
function getClientIp() {
    $ipAddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    return $ipAddress;
}

// Sanitize input to prevent XSS and other attacks
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

// Calculate call credits based on destination number and duration
function calculateCallCredits($destinationNumber, $duration) {
    // Base rate per minute
    $creditsPerMinute = CREDITS_PER_MINUTE;
    
    // For international calls, calculate based on country code
    if (substr($destinationNumber, 0, 1) === '+' && substr($destinationNumber, 1, 1) !== '1') {
        // Extract country code (this is a simplified approach)
        $countryCode = substr($destinationNumber, 1, 2);
        
        // Adjust rate based on country (simplified example)
        switch ($countryCode) {
            case '44': // UK
                $creditsPerMinute *= 1.2;
                break;
            case '91': // India
                $creditsPerMinute *= 1.5;
                break;
            // Add more country codes as needed
            default:
                $creditsPerMinute *= 2.0; // Default international rate
        }
    }
    
    // Convert duration to minutes (rounded up)
    $minutes = ceil($duration / 60);
    
    // Calculate total credits
    return $minutes * $creditsPerMinute;
}

// Deduct credits from user account
function deductCreditsFromUser($userId, $credits) {
    global $pdo;
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get current user credits
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Check if user has enough credits
        if ($user['credits'] < $credits) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Insufficient credits'
            ];
        }
        
        // Deduct credits
        $newBalance = $user['credits'] - $credits;
        $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Credits deducted successfully',
            'new_balance' => $newBalance
        ];
    } catch (\PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
} 