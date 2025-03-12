<?php
/**
 * Helper Functions
 * 
 * This file contains common utility functions
 */

// Custom error handler to log errors
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/../logs/php-critical-errors.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $errorType = match($errno) {
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        default => 'Unknown Error'
    };
    
    $message = date('Y-m-d H:i:s') . " - $errorType [$errno]: $errstr in $errfile on line $errline\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    
    // If it's a fatal error, also log the backtrace
    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        $trace = debug_backtrace();
        $traceOutput = "Backtrace:\n";
        foreach ($trace as $i => $step) {
            $traceOutput .= "#$i " . ($step['file'] ?? '') . "(" . ($step['line'] ?? '') . "): ";
            if (isset($step['class'])) {
                $traceOutput .= $step['class'] . $step['type'];
            }
            $traceOutput .= $step['function'] . "()\n";
        }
        file_put_contents($logFile, $traceOutput, FILE_APPEND);
    }
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set the custom error handler
set_error_handler("customErrorHandler", E_ALL);

/**
 * Sanitize input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format phone number
 * 
 * @param string $phoneNumber Phone number to format
 * @return string Formatted phone number (E.164 format)
 */
function formatPhoneNumber($phoneNumber) {
    // Remove non-numeric characters
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Add + prefix if missing
    if (substr($phoneNumber, 0, 1) != '+') {
        // Check if country code is included
        if (strlen($phoneNumber) > 10) {
            $phoneNumber = '+' . $phoneNumber;
        } else {
            // Assume US number if no country code
            $phoneNumber = '+1' . $phoneNumber;
        }
    }
    
    return $phoneNumber;
}

/**
 * Format duration in seconds to human-readable format
 * 
 * @param int $seconds Duration in seconds
 * @return string Formatted duration (e.g. "1h 23m 45s")
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $result = '';
    if ($hours > 0) {
        $result .= $hours . 'h ';
    }
    if ($minutes > 0 || $hours > 0) {
        $result .= $minutes . 'm ';
    }
    $result .= $secs . 's';
    
    return $result;
}

/**
 * Format credits with 2 decimal places
 * 
 * @param float $credits Credits amount
 * @return string Formatted credits
 */
function formatCredits($credits) {
    return number_format($credits, 2);
}

/**
 * Format amount as currency
 * 
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Generate a random string
 * 
 * @param int $length String length
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    return $protocol . '://' . $host;
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Check if request is AJAX
 * 
 * @return bool Is AJAX request
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Set session flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear session flash message
 * 
 * @return array|null Flash message or null if none
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $type Log type
 * @return void
 */
function logMessage($message, $type = 'info') {
    $logFile = __DIR__ . '/../logs/app-log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Check if user is authenticated
 * 
 * @return bool User is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool User is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Get setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $result = dbFetchRow($sql, [$key]);
    
    if ($result && isset($result['setting_value'])) {
        return $result['setting_value'];
    }
    
    return $default;
}

/**
 * Update setting value
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success or failure
 */
function updateSetting($key, $value) {
    // Check if setting exists
    $sql = "SELECT id FROM settings WHERE setting_key = ?";
    $result = dbFetchRow($sql, [$key]);
    
    if ($result) {
        // Update setting
        return dbUpdate('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    } else {
        // Insert setting
        return dbInsert('settings', [
            'setting_key' => $key,
            'setting_value' => $value
        ]) !== false;
    }
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool Token is valid
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token (alias for verifyCsrfToken)
 * 
 * @param string $token Token to validate
 * @return bool Token is valid
 */
function validateCsrfToken($token) {
    return verifyCsrfToken($token);
}

/**
 * Send an email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message (HTML or plain text)
 * @param string $fromName Sender name (optional)
 * @param string $fromEmail Sender email (optional)
 * @param string $altMessage Alternative plain text message (optional)
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $message, $fromName = '', $fromEmail = '', $altMessage = '') {
    // Set default sender details if not provided
    if (empty($fromName)) {
        $fromName = SITE_NAME;
    }
    
    if (empty($fromEmail)) {
        $fromEmail = SUPPORT_EMAIL;
    }
    
    // Check if html content
    $isHtml = strpos($message, '<html>') !== false || strpos($message, '<body>') !== false;
    
    // Create a detailed log entry of attempts
    logMessage("Attempting to send email to: $to with subject: $subject", 'debug');
    
    // Check for PHPMailer with better error handling
    $phpmailerExists = class_exists('PHPMailer\PHPMailer\PHPMailer');
    
    if (!$phpmailerExists) {
        logMessage("PHPMailer class not found. Checking if autoloader is working...", 'warning');
        
        // Check if autoloader might be failing
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            logMessage("Vendor/autoload.php not found. Composer dependencies may not be installed.", 'error');
        } else {
            logMessage("Vendor/autoload.php exists, but PHPMailer class can't be found. Try running 'composer dump-autoload'", 'error');
        }
        
        // Fall back to mail() function
        logMessage("PHPMailer not available, falling back to mail() function", 'error');
        
        // Headers for plain text or HTML
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        // Log email attempt
        logMessage("Sending email to: $to, Subject: $subject via mail() function", 'debug');
        
        // Send email
        $result = mail($to, $subject, $message, $headers);
        
        // Log result
        if ($result) {
            logMessage("Email sent successfully to: $to via mail() function", 'info');
        } else {
            logMessage("Failed to send email to: $to via mail() function. The server may have disabled mail() function.", 'error');
            
            // Check common issues
            if (!ini_get('sendmail_path')) {
                logMessage("sendmail_path is not configured in PHP", 'error');
            }
        }
        
        return $result;
    }
    
    try {
        // Check if SMTP settings are defined
        if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            logMessage("SMTP settings not properly defined. Check your mail configuration.", 'error');
            throw new Exception("SMTP configuration missing");
        }
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Detailed logging
        logMessage("PHPMailer instantiated successfully", 'debug');
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Enable debugging in development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = 2; // Output debug info
            $mail->Debugoutput = function($str, $level) {
                logMessage("PHPMailer [$level]: $str", 'debug');
            };
        }
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->addReplyTo($fromEmail, $fromName);
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        // Add alternative plain text if provided or generate from HTML
        if (!empty($altMessage)) {
            $mail->AltBody = $altMessage;
        } elseif ($isHtml) {
            // Simple HTML to plain text conversion if no alt text provided
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</h1>', '</h2>', '</h3>'], "\n", $message));
        }
        
        // Log email attempt
        logMessage("Sending email via SMTP to: $to, Subject: $subject", 'debug');
        
        // Send email
        $result = $mail->send();
        
        // Log result
        logMessage("Email sent successfully to: $to via SMTP", 'info');
        
        return true;
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        $phpmailerError = isset($mail) ? $mail->ErrorInfo : "PHPMailer not instantiated";
        
        logMessage("Failed to send email to: $to. Error: " . $errorMessage, 'error');
        logMessage("PHPMailer error details: " . $phpmailerError, 'error');
        
        // Attempt to diagnose common SMTP issues
        if (strpos($errorMessage, 'connect') !== false || strpos($phpmailerError, 'connect') !== false) {
            logMessage("SMTP connection error. Check if your hosting allows outgoing SMTP connections.", 'error');
        }
        
        if (strpos($errorMessage, 'authenticate') !== false || strpos($phpmailerError, 'authenticate') !== false) {
            logMessage("SMTP authentication failed. Check your username and password.", 'error');
        }
        
        return false;
    }
}

/**
 * Format date to readable format
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format a timestamp as a relative time string
 *
 * @param string $timestamp The timestamp to format
 * @return string Formatted relative time (e.g., "2 hours ago")
 */
function timeAgo($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    }
    
    $intervals = [
        1                => ['minute', 'minutes'],
        60               => ['hour', 'hours'],
        60 * 24          => ['day', 'days'],
        60 * 24 * 7      => ['week', 'weeks'],
        60 * 24 * 30     => ['month', 'months'],
        60 * 24 * 365    => ['year', 'years']
    ];
    
    foreach ($intervals as $secs => $interval) {
        $dividedDiff = $diff / $secs;
        
        if ($dividedDiff < 60) {
            $pluralize = $dividedDiff == 1 ? $interval[0] : $interval[1];
            return floor($dividedDiff) . ' ' . $pluralize . ' ago';
        }
    }
    
    return date('F j, Y', $time);
}

/**
 * Updates user credits in the session
 * Call this function anytime credits are changed
 * 
 * @param int $userId User ID
 * @return bool Success or failure
 */
function updateSessionCredits($userId) {
    global $pdo;
    
    try {
        // Get current credits from database
        $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update session variable
            $_SESSION['user_credits'] = $user['credits'];
            error_log("Updated session credits for user ID {$userId} to {$user['credits']}", 0, 'logs/credits-debug.log');
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Failed to update session credits: " . $e->getMessage(), 0, 'logs/credits-debug.log');
        return false;
    }
} 