<?php
/**
 * Logger Functions
 * 
 * This file contains functions for logging
 */

/**
 * Log levels
 */
define('LOG_DEBUG', 0);
define('LOG_INFO', 1);
define('LOG_WARNING', 2);
define('LOG_ERROR', 3);
define('LOG_CRITICAL', 4);

/**
 * Write log message to file
 * 
 * @param string $message Log message
 * @param int $level Log level
 * @param string $module Module name
 * @return void
 */
function writeLog($message, $level = LOG_INFO, $module = 'app') {
    // Create log directory if it doesn't exist
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Determine log level text
    $levelText = '';
    switch ($level) {
        case LOG_DEBUG:
            $levelText = 'DEBUG';
            break;
        case LOG_INFO:
            $levelText = 'INFO';
            break;
        case LOG_WARNING:
            $levelText = 'WARNING';
            break;
        case LOG_ERROR:
            $levelText = 'ERROR';
            break;
        case LOG_CRITICAL:
            $levelText = 'CRITICAL';
            break;
        default:
            $levelText = 'INFO';
    }
    
    // Format log entry
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$levelText] [$module] $message" . PHP_EOL;
    
    // Write to module-specific log file
    $logFile = $logDir . '/' . $module . '-log.txt';
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Log debug message
 * 
 * @param string $message Log message
 * @param string $module Module name
 * @return void
 */
function logDebug($message, $module = 'app') {
    writeLog($message, LOG_DEBUG, $module);
}

/**
 * Log info message
 * 
 * @param string $message Log message
 * @param string $module Module name
 * @return void
 */
function logInfo($message, $module = 'app') {
    writeLog($message, LOG_INFO, $module);
}

/**
 * Log warning message
 * 
 * @param string $message Log message
 * @param string $module Module name
 * @return void
 */
function logWarning($message, $module = 'app') {
    writeLog($message, LOG_WARNING, $module);
}

/**
 * Log error message
 * 
 * @param string $message Log message
 * @param string $module Module name
 * @return void
 */
function logError($message, $module = 'app') {
    writeLog($message, LOG_ERROR, $module);
}

/**
 * Log critical message
 * 
 * @param string $message Log message
 * @param string $module Module name
 * @return void
 */
function logCritical($message, $module = 'app') {
    writeLog($message, LOG_CRITICAL, $module);
}

/**
 * Log payment events
 * 
 * @param string $message Log message
 * @param int $userId User ID
 * @param array $data Additional data
 * @return void
 */
function logPayment($message, $userId = null, $data = []) {
    $userInfo = $userId ? "User ID: $userId" : "Anonymous";
    $dataString = !empty($data) ? " - Data: " . json_encode($data) : "";
    writeLog("$message - $userInfo$dataString", LOG_INFO, 'payment');
}

/**
 * Log call events
 * 
 * @param string $message Log message
 * @param int $userId User ID
 * @param string $callSid Twilio call SID
 * @param array $data Additional data
 * @return void
 */
function logCall($message, $userId = null, $callSid = null, $data = []) {
    $userInfo = $userId ? "User ID: $userId" : "Anonymous";
    $callInfo = $callSid ? "Call SID: $callSid" : "";
    $dataString = !empty($data) ? " - Data: " . json_encode($data) : "";
    writeLog("$message - $userInfo - $callInfo$dataString", LOG_INFO, 'call');
}

/**
 * Log authentication events
 * 
 * @param string $message Log message
 * @param string $email User email
 * @param string $ip User IP address
 * @return void
 */
function logAuth($message, $email = null, $ip = null) {
    $emailInfo = $email ? "Email: $email" : "Anonymous";
    $ipInfo = $ip ? "IP: $ip" : "";
    writeLog("$message - $emailInfo - $ipInfo", LOG_INFO, 'auth');
}

/**
 * Log API events
 * 
 * @param string $message Log message
 * @param string $endpoint API endpoint
 * @param array $data Request/response data
 * @return void
 */
function logApi($message, $endpoint = null, $data = []) {
    $endpointInfo = $endpoint ? "Endpoint: $endpoint" : "";
    $dataString = !empty($data) ? " - Data: " . json_encode($data) : "";
    writeLog("$message - $endpointInfo$dataString", LOG_INFO, 'api');
}

/**
 * Log error with exception details
 * 
 * @param Exception $exception Exception object
 * @param string $module Module name
 * @return void
 */
function logException($exception, $module = 'app') {
    $message = "Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . 
               " on line " . $exception->getLine() . 
               "\nStack trace: " . $exception->getTraceAsString();
    
    writeLog($message, LOG_ERROR, $module);
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIp() {
    $ip = '0.0.0.0';
    
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ip = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ip = $_SERVER['REMOTE_ADDR'];
    
    return $ip;
}

/**
 * Log a message to a specific log file
 * 
 * @param string $filename The log file name
 * @param string $message The message to log
 * @param array $data Optional data to log
 * @return void
 */
function logToFile($filename, $message, $data = []) {
    $logDir = __DIR__ . '/../logs/';
    $logFile = $logDir . $filename;
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Format timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Format message
    $logEntry = "[$timestamp] $message";
    
    // Add data if provided
    if (!empty($data)) {
        $logEntry .= " - " . json_encode($data);
    }
    
    // Add new line and write to file
    $logEntry .= PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
} 