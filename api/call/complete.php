<?php
/**
 * API: Complete Call
 * 
 * Records call details after a call has ended
 */

// Set content type for response
header('Content-Type: application/json');

// Create log file
$logFile = __DIR__ . '/../../logs/call-complete-debug.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Get JSON request data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Log request
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call complete request received\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Data: " . json_encode($data) . "\n", FILE_APPEND);

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id']) && empty($data['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: User not authenticated\n", FILE_APPEND);
    exit;
}

// Check required parameters
if (empty($data['call_sid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing call SID'
    ]);
    exit;
}

// Load required models
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Call.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../config/twilio.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    // Get user ID from request or session
    $userId = !empty($data['userId']) ? $data['userId'] : $_SESSION['user_id'];
    
    // Make sure userId is not empty
    if (empty($userId)) {
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: User ID is required but was empty\n", FILE_APPEND);
        exit;
    }
    
    $callSid = $data['call_sid'];
    $duration = intval($data['duration'] ?? 0);
    
    // Log the user ID being used
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using user ID: {$userId}\n", FILE_APPEND);
    
    // Check if call already exists in database
    $existingCall = getCallByTwilioSid($callSid);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Searching for call with SID: {$callSid}\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Existing call found: " . ($existingCall ? "Yes (ID: {$existingCall['id']})" : "No") . "\n", FILE_APPEND);

    // If call exists and has already been completed and billed, don't process it again
    if ($existingCall && $existingCall['status'] === 'completed' && $existingCall['credits_used'] > 0) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call already completed and billed, skipping: {$existingCall['id']}\n", FILE_APPEND);
        
        // Get user credits
        $user = getUserById($userId);
        $credits = $user ? $user['credits'] : 0;
        
        // Return success response
        echo json_encode([
            'success' => true,
            'credits' => formatCredits($credits),
            'message' => 'Call already processed'
        ]);
        exit;
    }
    
    // Check if there's a related outbound call that has been billed
    // Typically, a client-initiated call will have a related outbound-dial call
    try {
        // This is a simplified way to check - in a full implementation you might need to check timestamps, etc.
        $db = getPdo();
        $sql = "SELECT * FROM calls WHERE user_id = ? AND twilio_call_sid != ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND status = 'completed' AND credits_used > 0 ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $callSid]);
        $relatedCall = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($relatedCall) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found a recently billed related call: {$relatedCall['id']} with SID: {$relatedCall['twilio_call_sid']}\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Will use related call for billing reference\n", FILE_APPEND);
            
            // If there's a related call and we're processing the client-side call, we might want to 
            // update some fields but not double-bill
            if ($existingCall) {
                // Update the existing call without changing billing
                $updateData = [
                    'status' => 'completed',
                    'ended_at' => date('Y-m-d H:i:s')
                ];
                
                if ($duration > 0 && $existingCall['duration'] == 0) {
                    $updateData['duration'] = $duration;
                }
                
                // Update call without affecting billing
                updateCall($existingCall['id'], $updateData);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated call {$existingCall['id']} without changing billing\n", FILE_APPEND);
            } else {
                // If somehow we don't have a record of this call, create one but link to the related call
                // to avoid double billing
                $callData = [
                    'user_id' => $userId,
                    'twilio_call_sid' => $callSid,
                    'destination_number' => $relatedCall['destination_number'],
                    'status' => 'completed',
                    'duration' => $duration,
                    'credits_used' => 0, // No billing
                    'related_call_id' => $relatedCall['id'], // Link to the call that was billed
                    'started_at' => date('Y-m-d H:i:s', time() - $duration),
                    'ended_at' => date('Y-m-d H:i:s')
                ];
                
                $callId = dbInsert('calls', $callData);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Created new non-billing call record ID: {$callId}, linked to call {$relatedCall['id']}\n", FILE_APPEND);
            }
            
            // Return success with user's current credits
            $user = getUserById($userId);
            $credits = $user ? $user['credits'] : 0;
            
            echo json_encode([
                'success' => true,
                'credits' => formatCredits($credits),
                'message' => 'Call recorded successfully (related call billed)'
            ]);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call processed using related call for billing\n", FILE_APPEND);
            exit;
        }
    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error checking for related calls: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // If no call found by SID, check session for last call ID with temp SID
    if (!$existingCall && isset($_SESSION['last_call_id']) && isset($_SESSION['last_call_temp_sid'])) {
        $lastCallId = $_SESSION['last_call_id'];
        $tempCallSid = $_SESSION['last_call_temp_sid'];
        $lastCallNumber = $_SESSION['last_call_number'] ?? null;
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Checking last call from session: ID {$lastCallId}, Temp SID: {$tempCallSid}, Number: {$lastCallNumber}\n", FILE_APPEND);
        
        // Get the call by ID
        $callFromSession = getCallById($lastCallId);
        
        // Also, try to find any call with the temp SID pattern
        if (!$callFromSession && !empty($userId)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Looking for recent temp calls for user ID: {$userId}\n", FILE_APPEND);
            
            // Try to find a recent call with temp SID for this user
            $sql = "SELECT * FROM calls WHERE user_id = ? AND twilio_call_sid LIKE 'temp_%' AND status = 'initiated' ORDER BY created_at DESC LIMIT 1";
            $stmt = getPdo()->prepare($sql);
            $stmt->execute([$userId]);
            $tempCall = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tempCall) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found recent temp call with ID: {$tempCall['id']}, SID: {$tempCall['twilio_call_sid']}\n", FILE_APPEND);
                $callFromSession = $tempCall;
                $lastCallId = $tempCall['id'];
                $tempCallSid = $tempCall['twilio_call_sid'];
            }
        }
        
        if ($callFromSession) {
            // We found a call with a temp SID, update it with the real SID
            $updateData = [
                'twilio_call_sid' => $callSid,
                'status' => 'completed', // Update status
                'duration' => $duration,
                'ended_at' => date('Y-m-d H:i:s')
            ];
            
            // Make sure we have a valid destination number
            if ($callFromSession['destination_number'] === 'unknown' && !empty($data['To'])) {
                $updateData['destination_number'] = formatPhoneNumber($data['To']);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Setting destination number to: {$data['To']}\n", FILE_APPEND);
            } elseif ($callFromSession['destination_number'] === 'unknown' && !empty($lastCallNumber)) {
                $updateData['destination_number'] = $lastCallNumber;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Setting destination number from session: {$lastCallNumber}\n", FILE_APPEND);
            }
            
            // Calculate credits used (using default rate if not specified)
            $twilioRate = 0.015; // Default rate
            $multiplier = 200; // Default multiplier
            $creditsUsed = null;
            
            // Try to fetch the actual call cost from Twilio API
            try {
                // Make sure we load the required files
                require_once __DIR__ . '/../../config/twilio.php';
                
                if (!empty($callSid)) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Attempting to get call cost for SID: {$callSid}\n", FILE_APPEND);
                    
                    // Try to get call details from Twilio
                    $callDetails = getTwilioCallDetails($callSid);
                    
                    if ($callDetails && isset($callDetails['price']) && $callDetails['price'] !== null) {
                        // Convert price to a positive number (Twilio returns negative values)
                        $actualCost = abs(floatval($callDetails['price']));
                        
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Successfully retrieved actual call cost from Twilio: {$actualCost}\n", FILE_APPEND);
                        
                        // Add fixed cost component before multiplying
                        $actualCost = $actualCost + 0.004;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Added fixed cost component (0.004): {$actualCost}\n", FILE_APPEND);
                        
                        // Calculate credits based on actual cost with multiplier
                        $creditsUsed = $actualCost * $multiplier;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Calculated credits using actual cost: $actualCost × $multiplier = {$creditsUsed}\n", FILE_APPEND);
                    } else {
                        // If price information is not available, log it
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Price information not available from Twilio API call\n", FILE_APPEND);
                    }
                }
                
                // If we couldn't get the actual cost, use the default calculation
                if ($creditsUsed === null) {
                    $durationMinutes = max(0.1, $duration / 60); // Minimum of 0.1 minute
                    $creditsUsed = $twilioRate * $durationMinutes * $multiplier;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using default calculation: $twilioRate × $durationMinutes × $multiplier = {$creditsUsed}\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                // Log the error
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error getting call cost: " . $e->getMessage() . "\n", FILE_APPEND);
                
                // Fall back to default calculation
                $durationMinutes = max(0.1, $duration / 60); // Minimum of 0.1 minute
                $creditsUsed = $twilioRate * $durationMinutes * $multiplier;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using default calculation after error: $twilioRate × $durationMinutes × $multiplier = {$creditsUsed}\n", FILE_APPEND);
            }
            
            $updateData['credits_used'] = $creditsUsed;
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updating call ID {$lastCallId} with real SID: {$callSid} and duration: {$duration}, credits: {$creditsUsed}\n", FILE_APPEND);
            
            // Update the call record
            updateCall($lastCallId, $updateData);
            
            // Subtract credits from user if we have a successful update
            subtractUserCredits($userId, $creditsUsed);
            
            // Now get the updated call
            $existingCall = getCallById($lastCallId);
            
            // Get updated user credits
            $user = getUserById($userId);
            $credits = $user ? $user['credits'] : 0;
            
            // Return success response
            echo json_encode([
                'success' => true,
                'credits' => formatCredits($credits),
                'message' => 'Call record updated successfully'
            ]);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call updated successfully\n", FILE_APPEND);
            exit; // Exit early since we've handled this call
        }
    }
    
    if ($existingCall) {
        // Update existing call
        $callId = $existingCall['id'];
        $updateData = [
            'duration' => $duration,
            'status' => 'completed',
            'ended_at' => date('Y-m-d H:i:s')
        ];
        
        // If the existing call has an unknown destination number, update it
        if ($existingCall['destination_number'] === 'unknown' || empty($existingCall['destination_number'])) {
            // First try the destination from request data
            if (!empty($data['To'])) {
                $updateData['destination_number'] = formatPhoneNumber($data['To']);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updating destination number to: {$updateData['destination_number']}\n", FILE_APPEND);
            } 
            // Then try from session
            elseif (isset($_SESSION['last_call_number']) && !empty($_SESSION['last_call_number'])) {
                $updateData['destination_number'] = $_SESSION['last_call_number'];
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updating destination number from session: {$updateData['destination_number']}\n", FILE_APPEND);
            }
        }
        
        completeCall($callId, $updateData);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated existing call record ID: {$callId}\n", FILE_APPEND);
    } else {
        // Create new call record
        $destNumber = 'unknown';
        
        // Try to get destination number from different sources
        if (!empty($data['To'])) {
            $destNumber = formatPhoneNumber($data['To']);
        } elseif (!empty($data['to'])) {
            $destNumber = formatPhoneNumber($data['to']);
        } elseif (isset($_SESSION['last_call_number']) && !empty($_SESSION['last_call_number'])) {
            $destNumber = $_SESSION['last_call_number'];
        }
        
        // Create call data
        $callData = [
            'user_id' => $userId,
            'twilio_call_sid' => $callSid,
            'destination_number' => $destNumber,
            'status' => 'completed',
            'duration' => $duration,
            'started_at' => date('Y-m-d H:i:s', time() - $duration),
            'ended_at' => date('Y-m-d H:i:s')
        ];
        
        // Log the destination number we're using
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using destination number: {$callData['destination_number']}\n", FILE_APPEND);
        
        // Calculate credits used (using default rate if not specified)
        $twilioRate = 0.015; // Default rate
        $multiplier = 200; // Default multiplier
        $creditsUsed = null;
        
        // Try to fetch the actual call cost from Twilio API
        try {
            // Make sure we load the required files
            require_once __DIR__ . '/../../config/twilio.php';
            
            if (!empty($callSid)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Attempting to get call cost for SID: {$callSid}\n", FILE_APPEND);
                
                // Try to get call details from Twilio
                $callDetails = getTwilioCallDetails($callSid);
                
                if ($callDetails && isset($callDetails['price']) && $callDetails['price'] !== null) {
                    // Convert price to a positive number (Twilio returns negative values)
                    $actualCost = abs(floatval($callDetails['price']));
                    
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Successfully retrieved actual call cost from Twilio: {$actualCost}\n", FILE_APPEND);
                    
                    // Add fixed cost component before multiplying
                    $actualCost = $actualCost + 0.004;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Added fixed cost component (0.004): {$actualCost}\n", FILE_APPEND);
                    
                    // Calculate credits based on actual cost with multiplier
                    $creditsUsed = $actualCost * $multiplier;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Calculated credits using actual cost: $actualCost × $multiplier = {$creditsUsed}\n", FILE_APPEND);
                } else {
                    // If price information is not available, log it
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Price information not available from Twilio API call\n", FILE_APPEND);
                }
            }
            
            // If we couldn't get the actual cost, use the default calculation
            if ($creditsUsed === null) {
                $durationMinutes = max(0.1, $duration / 60); // Minimum of 0.1 minute
                $creditsUsed = $twilioRate * $durationMinutes * $multiplier;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using default calculation: $twilioRate × $durationMinutes × $multiplier = {$creditsUsed}\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            // Log the error
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error getting call cost: " . $e->getMessage() . "\n", FILE_APPEND);
            
            // Fall back to default calculation
            $durationMinutes = max(0.1, $duration / 60); // Minimum of 0.1 minute
            $creditsUsed = $twilioRate * $durationMinutes * $multiplier;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using default calculation after error: $twilioRate × $durationMinutes × $multiplier = {$creditsUsed}\n", FILE_APPEND);
        }
        
        $callData['credits_used'] = $creditsUsed;
        
        // Create call record
        $callId = dbInsert('calls', $callData);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Created new call record ID: {$callId}\n", FILE_APPEND);
        
        // Subtract credits from user
        if ($callId) {
            subtractUserCredits($userId, $creditsUsed);
        }
    }
    
    // Get updated user credits
    $user = getUserById($userId);
    $credits = $user ? $user['credits'] : 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'credits' => formatCredits($credits),
        'message' => 'Call recorded successfully'
    ]);
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call recorded successfully\n", FILE_APPEND);
} catch (Exception $e) {
    // Log error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error recording call: ' . $e->getMessage()
    ]);
} 