<?php
/**
 * Twilio Status Callback Handler
 * 
 * This file receives status updates from Twilio about calls
 */

// Set content type for response
header('Content-Type: application/json');

// Create log file
$logFile = __DIR__ . '/../../logs/status-callback-debug.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Wrap everything in a try-catch to prevent 500 errors
try {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Status callback received\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);

    // Extract call data
    $callSid = $_POST['CallSid'] ?? 'unknown';
    $parentCallSid = $_POST['ParentCallSid'] ?? null;
    $callStatus = $_POST['CallStatus'] ?? 'unknown';
    $callDuration = intval($_POST['CallDuration'] ?? 0);
    $from = $_POST['From'] ?? '';
    $to = $_POST['To'] ?? '';
    $direction = $_POST['Direction'] ?? '';
    
    // Log important call relationship information
    if ($parentCallSid) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - This is a child call of parent SID: $parentCallSid\n", FILE_APPEND);
    }
    
    // Only process completed calls
    if ($callStatus !== 'completed' && $callStatus !== 'answered') {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Skipping non-completed call with status: $callStatus\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Call status acknowledged']);
        exit;
    }

    // Log key data
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call SID: $callSid, Status: $callStatus, Duration: $callDuration\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - From: $from, To: $to\n", FILE_APPEND);

    // Get the user ID from query parameter if available
    $userId = $_GET['user_id'] ?? null;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - User ID from URL: " . ($userId ?? 'none') . "\n", FILE_APPEND);

    // Get session data
    session_start();
    $lastCallId = $_SESSION['last_call_id'] ?? null;
    $lastTempSid = $_SESSION['last_call_temp_sid'] ?? null;
    $lastCallNumber = $_SESSION['last_call_number'] ?? null;
    session_write_close();
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Last call ID from session: " . ($lastCallId ?? 'none') . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Temp call SID from session: " . ($lastTempSid ?? 'none') . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Last call number from session: " . ($lastCallNumber ?? 'none') . "\n", FILE_APPEND);

    // Only proceed if we have the needed data
    if ($callSid !== 'unknown') {
        // Required for calculating call cost
        require_once __DIR__ . '/../../config/twilio.php';
        require_once __DIR__ . '/../../config/database.php';
        $db = getDbConnection();
        
        if (!$db) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Failed to connect to database\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }

        // First try to find the call by its SID
        $sql = "SELECT * FROM calls WHERE twilio_call_sid = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$callSid]);
        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If we couldn't find the call by SID, look for a temp call
        if (!$call) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - No existing call record found for SID: $callSid\n", FILE_APPEND);
            
            // Try to find by temporary SID from session
            if ($lastCallId && $lastTempSid) {
                $sql = "SELECT * FROM calls WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$lastCallId]);
                $call = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($call) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found existing call record ID: $lastCallId\n", FILE_APPEND);
                }
            }
            
            // If still not found, try to find by user ID and temporary SID pattern
            if (!$call && $userId) {
                $sql = "SELECT * FROM calls 
                        WHERE user_id = ? 
                        AND twilio_call_sid LIKE 'temp_%' 
                        AND status = 'initiated' 
                        ORDER BY id DESC LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                $call = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($call) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found existing call record ID: {$call['id']}\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found existing call record ID: {$call['id']}\n", FILE_APPEND);
            
            // Check if call has already been processed and completed - avoid double billing
            if ($call['status'] === 'completed' && $call['credits_used'] > 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call already completed and billed, skipping: {$call['id']}\n", FILE_APPEND);
                echo json_encode(['success' => true, 'message' => 'Call already processed']);
                exit;
            }
            
            // If this is a child call, check if the parent call has been billed
            if ($parentCallSid) {
                $parentSql = "SELECT * FROM calls WHERE twilio_call_sid = ?";
                $parentStmt = $db->prepare($parentSql);
                $parentStmt->execute([$parentCallSid]);
                $parentCall = $parentStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($parentCall && $parentCall['status'] === 'completed' && $parentCall['credits_used'] > 0) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Parent call already billed, linking to parent: {$parentCall['id']}\n", FILE_APPEND);
                    
                    // Update the current call to link to the parent but don't bill again
                    $sql = "UPDATE calls SET 
                            status = ?,
                            duration = ?,
                            related_call_id = ?,
                            ended_at = NOW()
                            WHERE id = ?";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$callStatus, $callDuration, $parentCall['id'], $call['id']]);
                    
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated call to link to parent, no duplicate billing\n", FILE_APPEND);
                    echo json_encode(['success' => true, 'message' => 'Call linked to parent']);
                    exit;
                }
            }
        }
        
        // If we found a call, update it
        if ($call) {
            $callId = $call['id'];
            $callUserId = $call['user_id'];
            
            // Check if call has already been processed and completed - avoid double billing
            if ($call['status'] === 'completed' && $call['credits_used'] > 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Call already completed and billed, skipping: {$call['id']}\n", FILE_APPEND);
                echo json_encode(['success' => true, 'message' => 'Call already processed']);
                exit;
            }
            
            // For outbound-dial calls, get detailed pricing as these calls contain the cost
            $useActualPricing = ($direction === 'outbound-dial');
            if ($useActualPricing) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - This is an outbound-dial call, will use for actual pricing\n", FILE_APPEND);
            } else if (strpos($from, 'client:') === 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - This is a client-originated call, may not have pricing\n", FILE_APPEND);
                
                // For client calls, check if there's a related outbound-dial call
                $outboundSql = "SELECT * FROM calls 
                                WHERE user_id = ? 
                                AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                                AND twilio_call_sid != ?
                                AND destination_number = ?
                                ORDER BY id DESC LIMIT 1";
                $outboundStmt = $db->prepare($outboundSql);
                $outboundStmt->execute([$callUserId, $callSid, $to]);
                $relatedCall = $outboundStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($relatedCall) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found related outbound call: {$relatedCall['id']}\n", FILE_APPEND);
                    
                    // If the related call has been billed, don't bill this one
                    if ($relatedCall['status'] === 'completed' && $relatedCall['credits_used'] > 0) {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Related call already billed, linking: {$relatedCall['id']}\n", FILE_APPEND);
                        
                        // Update the current call to link to the related call but don't bill again
                        $sql = "UPDATE calls SET 
                                status = ?,
                                duration = ?,
                                related_call_id = ?,
                                ended_at = NOW()
                                WHERE id = ?";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$callStatus, $callDuration, $relatedCall['id'], $callId]);
                        
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated call to link to related call, no duplicate billing\n", FILE_APPEND);
                        echo json_encode(['success' => true, 'message' => 'Call linked to related call']);
                        exit;
                    }
                }
            }
            
            // Try to get the actual cost from Twilio first
            $multiplier = 200; // Credit multiplier
            $cost = null;
            
            // Try to get actual call details from Twilio
            try {
                $callDetails = getTwilioCallDetails($callSid);
                
                if ($callDetails && isset($callDetails['price']) && $callDetails['price'] !== null) {
                    // Convert price to a positive number
                    $actualPrice = abs(floatval($callDetails['price']));
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Got actual price from Twilio: $actualPrice\n", FILE_APPEND);
                    
                    // Add fixed cost component before multiplying
                    $actualPrice = $actualPrice + 0.004;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Added fixed cost component (0.004): $actualPrice\n", FILE_APPEND);
                    
                    // Calculate cost based on actual price and multiplier
                    $cost = $actualPrice * $multiplier;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Calculated credits using actual price: $actualPrice × $multiplier = $cost\n", FILE_APPEND);
                } else {
                    // Fallback to default calculation
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Price not available from Twilio, using default calculation\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error getting Twilio call details: " . $e->getMessage() . "\n", FILE_APPEND);
            }
            
            // If we couldn't get the actual cost, fall back to default calculation
            if ($cost === null) {
                $twilioRate = 0.015; // Default rate per minute
                $callMinutes = max(0.1, $callDuration / 60); // Minimum of 0.1 minute (6 seconds)
                $cost = $twilioRate * $callMinutes * $multiplier;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Using default calculation: $twilioRate × $callMinutes × $multiplier = $cost\n", FILE_APPEND);
            }
            
            // Update the call record
            $sql = "UPDATE calls SET 
                    twilio_call_sid = ?,
                    status = ?,
                    duration = ?,
                    credits_used = ?,
                    ended_at = NOW()
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$callSid, $callStatus, $callDuration, $cost, $callId]);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated call ID: $callId with SID: $callSid, Status: $callStatus, Duration: $callDuration, Cost: $cost\n", FILE_APPEND);
            
            // Deduct credits from user if call was completed or had any duration
            if (($callStatus == 'completed' || $callStatus == 'busy') && $callUserId) {
                // Get current user credits
                $userSql = "SELECT credits FROM users WHERE id = ?";
                $userStmt = $db->prepare($userSql);
                $userStmt->execute([$callUserId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $newCredits = max(0, $user['credits'] - $cost);
                    
                    // Update user credits
                    $updateSql = "UPDATE users SET credits = ? WHERE id = ?";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->execute([$newCredits, $callUserId]);
                    
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Updated user $callUserId credits. Old: {$user['credits']}, New: $newCredits, Deducted: $cost\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - User not found: $callUserId\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - No matching call found for SID: $callSid\n", FILE_APPEND);
        }
    }
    
    // Always return a success response to Twilio
    echo json_encode(['success' => true]);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Response completed\n", FILE_APPEND);
    
} catch (Exception $e) {
    // Log the error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Always return a success response to Twilio even on error
    echo json_encode(['success' => true]);
} 