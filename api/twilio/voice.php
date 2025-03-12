<?php
/**
 * Twilio Voice Handler for Browser-to-Phone Calls
 */

// Set content type for TwiML
header("Content-Type: text/xml");

// Create log file
$logFile = __DIR__ . '/../../logs/voice-debug.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Voice request received\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);

// Log ALL incoming POST data for troubleshooting
$allPost = print_r($_POST, true);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - ALL POST data: $allPost\n", FILE_APPEND);

try {
    // Get request parameters 
    $to = $_POST['To'] ?? '';
    $userId = $_POST['userId'] ?? '';
    
    // Extract userId from Caller if empty
    if (empty($userId) && !empty($_POST['Caller'])) {
        $callerParts = explode(':', $_POST['Caller']);
        if (count($callerParts) > 1 && is_numeric($callerParts[1])) {
            $userId = $callerParts[1];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Extracted userId from Caller: $userId\n", FILE_APPEND);
        }
    }
    
    // Get the Twilio phone number from config
    require_once __DIR__ . '/../../config/twilio.php';
    
    // Set caller ID to Twilio number
    $callerId = TWILIO_PHONE_NUMBER;
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Destination: $to, CallerId: $callerId, UserId: $userId\n", FILE_APPEND);
    
    // Create a temporary call record if we have the data
    if ($userId && !empty($to)) {
        try {
            // Generate temporary SID
            $tempSid = 'temp_' . time() . '_' . rand(1000, 9999);
            
            // Store in session
            session_start();
            $_SESSION['last_call_id'] = null; // Will be set after insert
            $_SESSION['last_call_temp_sid'] = $tempSid;
            $_SESSION['last_call_number'] = $to;
            session_write_close();
            
            // Simple direct SQL approach for reliability
            require_once __DIR__ . '/../../config/database.php';
            $db = getDbConnection();
            
            if ($db) {
                $sql = "INSERT INTO calls (user_id, destination_number, twilio_call_sid, status, started_at) 
                        VALUES (?, ?, ?, 'initiated', NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId, $to, $tempSid]);
                $callId = $db->lastInsertId();
                
                // Update the session with the call ID
                session_start();
                $_SESSION['last_call_id'] = $callId;
                session_write_close();
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Created call record ID: $callId with tempSid: $tempSid\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Failed to connect to database\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Missing required data for call record: UserId=$userId, To=$to\n", FILE_APPEND);
    }
    
    // Always generate TwiML response
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    
    if (!empty($to)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Generated Dial with callerId: $callerId\n", FILE_APPEND);
        echo '<Dial callerId="' . htmlspecialchars($callerId) . '">';
        echo '<Number statusCallbackEvent="ringing answered completed"';
        echo ' statusCallback="' . htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/api/twilio/status-callback.php?user_id=' . $userId) . '"';
        echo ' statusCallbackMethod="POST">';
        echo htmlspecialchars($to);
        echo '</Number>';
        echo '</Dial>';
    } else {
        echo '<Say>Please provide a destination number.</Say>';
    }
    
    echo '</Response>';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Response completed\n", FILE_APPEND);
    
} catch (Exception $e) {
    // Log the error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Return a simple TwiML response even in case of error
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Say>Sorry, an error occurred. Please try again later.</Say>';
    echo '</Response>';
} 