<?php
/**
 * Call Model
 * 
 * Call data management
 */

/**
 * Create a new call record
 * 
 * @param array $callData Call data array with keys: user_id, destination_number, caller_id, status, etc.
 * @return int|false Call ID or false on failure
 */
if (!function_exists('createCall')) {
    function createCall($callData) {
        // If old-style parameters were passed, handle them for backward compatibility
        if (!is_array($callData)) {
            $userId = $callData;
            $destinationNumber = func_get_arg(1);
            $twilioCallSid = func_get_arg(2);
            
            // Convert to array format
            $callData = [
                'user_id' => $userId,
                'destination_number' => $destinationNumber,
                'twilio_call_sid' => $twilioCallSid,
                'status' => 'initiated',
                'started_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Format phone number
        if (isset($callData['destination_number'])) {
            $callData['destination_number'] = formatPhoneNumber($callData['destination_number']);
        }
        
        // Create call record
        $callId = dbInsert('calls', $callData);
        
        if ($callId) {
            logCall("Call started", $callData['user_id'], $callData['twilio_call_sid'] ?? null, [
                'destination' => $callData['destination_number']
            ]);
            return $callId;
        }
        
        logCall("Failed to create call record", $callData['user_id'], $callData['twilio_call_sid'] ?? null, [
            'destination' => $callData['destination_number']
        ]);
        return false;
    }
}

/**
 * Complete a call
 * 
 * @param string $twilioCallSid Twilio call SID
 * @param int $duration Call duration in seconds
 * @param float $twilioRate Twilio rate per minute
 * @return bool Success or failure
 */
if (!function_exists('completeCall')) {
    function completeCall($twilioCallSid, $duration, $twilioRate = 0.015) {
        // Get call record
        $sql = "SELECT * FROM calls WHERE twilio_call_sid = ?";
        $call = dbFetchRow($sql, [$twilioCallSid]);
        
        if (!$call) {
            logCall("Call completion failed: Call not found", null, $twilioCallSid);
            return false;
        }
        
        // Calculate credits used
        $multiplier = getSetting('credit_multiplier', 200);
        $creditsUsed = calculateCallCost($twilioRate, $duration, $multiplier);
        
        // Update call record
        $success = dbUpdate('calls', [
            'duration' => $duration,
            'status' => 'completed',
            'credits_used' => $creditsUsed,
            'ended_at' => date('Y-m-d H:i:s')
        ], 'twilio_call_sid = ?', [$twilioCallSid]);
        
        if ($success) {
            // Subtract credits from user
            $success = subtractUserCredits($call['user_id'], $creditsUsed);
            
            if (!$success) {
                logCall("Failed to subtract credits from user", $call['user_id'], $twilioCallSid, [
                    'credits' => $creditsUsed
                ]);
            }
            
            logCall("Call completed", $call['user_id'], $twilioCallSid, [
                'duration' => $duration,
                'credits' => $creditsUsed
            ]);
            
            return true;
        }
        
        logCall("Call completion failed: Database error", $call['user_id'], $twilioCallSid);
        return false;
    }
}

/**
 * Get call by ID
 * 
 * @param int $callId Call ID
 * @return array|false Call data or false if not found
 */
if (!function_exists('getCallById')) {
    function getCallById($callId) {
        $sql = "SELECT * FROM calls WHERE id = ?";
        return dbFetchRow($sql, [$callId]);
    }
}

/**
 * Get call by Twilio call SID
 * 
 * @param string $twilioCallSid Twilio call SID
 * @return array|false Call data or false if not found
 */
function getCallByTwilioSid($twilioCallSid) {
    $sql = "SELECT * FROM calls WHERE twilio_call_sid = ?";
    return dbFetchRow($sql, [$twilioCallSid]);
}

/**
 * Get user's calls
 * 
 * @param int $userId User ID
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Calls data
 */
function getUserCalls($userId, $limit = 100, $offset = 0) {
    // Log that we're fetching calls for debugging purposes
    file_put_contents(__DIR__ . '/../logs/app-log.txt', 
        date('Y-m-d H:i:s') . " - Fetching calls for user ID: $userId, limit: $limit, offset: $offset\n", 
        FILE_APPEND
    );
    
    // Get all calls for the user, including those with related_call_id
    // Sort by ID in descending order to show newest calls first
    $sql = "SELECT * FROM calls WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?";
    $calls = dbFetchAll($sql, [$userId, $limit, $offset]);
    
    // Log the number of calls found
    file_put_contents(__DIR__ . '/../logs/app-log.txt', 
        date('Y-m-d H:i:s') . " - Found " . count($calls) . " calls for user ID: $userId\n", 
        FILE_APPEND
    );
    
    return $calls;
}

/**
 * Get user's call count
 * 
 * @param int $userId User ID
 * @return int Number of calls
 */
function getUserCallCount($userId) {
    $sql = "SELECT COUNT(*) as count FROM calls WHERE user_id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    return $result ? $result['count'] : 0;
}

/**
 * Get all calls
 * 
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Calls data
 */
function getAllCalls($limit = 100, $offset = 0) {
    $sql = "SELECT calls.*, users.name, users.email, users.phone_number 
            FROM calls 
            LEFT JOIN users ON calls.user_id = users.id 
            ORDER BY calls.id DESC LIMIT ? OFFSET ?";
    
    return dbFetchAll($sql, [$limit, $offset]);
}

/**
 * Get call count
 * 
 * @return int Number of calls
 */
function getCallCount() {
    $sql = "SELECT COUNT(*) as count FROM calls";
    $result = dbFetchRow($sql);
    
    return $result ? $result['count'] : 0;
}

/**
 * Get recent calls
 * 
 * @param int $limit Result limit
 * @return array Calls data
 */
function getRecentCalls($limit = 10) {
    $sql = "SELECT calls.*, users.name, users.email 
            FROM calls 
            LEFT JOIN users ON calls.user_id = users.id 
            ORDER BY calls.id DESC LIMIT ?";
    
    return dbFetchAll($sql, [$limit]);
}

/**
 * Get total call duration
 * 
 * @param int $userId User ID (optional, for specific user)
 * @return int Total duration in seconds
 */
function getTotalCallDuration($userId = null) {
    if ($userId) {
        $sql = "SELECT SUM(duration) as total FROM calls WHERE user_id = ?";
        $result = dbFetchRow($sql, [$userId]);
    } else {
        $sql = "SELECT SUM(duration) as total FROM calls";
        $result = dbFetchRow($sql);
    }
    
    return $result && $result['total'] ? $result['total'] : 0;
}

/**
 * Get total call cost
 * 
 * @param int $userId User ID (optional, for specific user)
 * @return float Total cost in credits
 */
function getTotalCallCost($userId = null) {
    if ($userId) {
        $sql = "SELECT SUM(credits_used) as total FROM calls WHERE user_id = ?";
        $result = dbFetchRow($sql, [$userId]);
    } else {
        $sql = "SELECT SUM(credits_used) as total FROM calls";
        $result = dbFetchRow($sql);
    }
    
    return $result && $result['total'] ? $result['total'] : 0;
}

/**
 * Get user call statistics
 * 
 * @param int $userId User ID
 * @return array Call statistics
 */
function getUserCallStats($userId) {
    $stats = [
        'total_calls' => getUserCallCount($userId),
        'total_duration' => getTotalCallDuration($userId),
        'total_credits' => getTotalCallCost($userId)
    ];
    
    // Format statistics
    $stats['formatted_duration'] = formatDuration($stats['total_duration']);
    $stats['formatted_credits'] = formatCredits($stats['total_credits']);
    
    return $stats;
}

/**
 * Search calls
 * 
 * @param string $query Search query
 * @param int $limit Result limit
 * @return array Calls data
 */
function searchCalls($query, $limit = 100) {
    $query = '%' . $query . '%';
    
    $sql = "SELECT calls.*, users.name, users.email 
            FROM calls 
            LEFT JOIN users ON calls.user_id = users.id 
            WHERE users.name LIKE ? OR users.email LIKE ? OR calls.destination_number LIKE ? OR calls.twilio_call_sid LIKE ? 
            ORDER BY calls.started_at DESC LIMIT ?";
    
    return dbFetchAll($sql, [$query, $query, $query, $query, $limit]);
}

/**
 * Update call record
 * 
 * @param int $callId Call ID
 * @param array $callData Call data to update
 * @return bool Success or failure
 */
if (!function_exists('updateCall')) {
    function updateCall($callId, $callData) {
        $success = dbUpdate('calls', $callData, 'id = ?', [$callId]);
        
        if ($success) {
            logCall("Call record updated", null, null, [
                'call_id' => $callId,
                'data' => json_encode($callData)
            ]);
            return true;
        }
        
        logCall("Failed to update call record", null, null, [
            'call_id' => $callId
        ]);
        return false;
    }
}

/**
 * Complete call and update record
 * 
 * @param int $callId Call ID
 * @param array $callData Call data
 * @return bool Success or failure
 */
if (!function_exists('completeCall')) {
    function completeCall($callId, $callData) {
        $callData['status'] = 'completed';
        $callData['ended_at'] = date('Y-m-d H:i:s');
        
        return updateCall($callId, $callData);
    }
}

/**
 * Get total number of calls
 * 
 * @return int Total calls
 */
function getTotalCalls() {
    $sql = "SELECT COUNT(*) as total FROM calls";
    $result = dbFetchRow($sql);
    
    return $result ? (int)$result['total'] : 0;
}

/**
 * Get user's filtered calls
 * 
 * @param int $userId User ID
 * @param array $filters Array of filters to apply
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Filtered calls data
 */
function getUserCallsFiltered($userId, $filters, $limit = 100, $offset = 0) {
    // Log that we're fetching filtered calls for debugging purposes
    file_put_contents(__DIR__ . '/../logs/app-log.txt', 
        date('Y-m-d H:i:s') . " - Fetching filtered calls for user ID: $userId, filters: " . json_encode($filters) . "\n", 
        FILE_APPEND
    );
    
    $params = [$userId];
    $sql = "SELECT * FROM calls WHERE user_id = ?";
    
    // Apply filters
    if (!empty($filters['destination_number'])) {
        $sql .= " AND destination_number LIKE ?";
        $params[] = '%' . $filters['destination_number'] . '%';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND started_at >= ?";
        $params[] = $filters['start_date'] . ' 00:00:00';
    }
    
    if (!empty($filters['end_date'])) {
        $sql .= " AND started_at <= ?";
        $params[] = $filters['end_date'] . ' 23:59:59';
    }
    
    // Sort by ID in descending order to show newest calls first
    $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $calls = dbFetchAll($sql, $params);
    
    // Log the number of calls found
    file_put_contents(__DIR__ . '/../logs/app-log.txt', 
        date('Y-m-d H:i:s') . " - Found " . count($calls) . " filtered calls for user ID: $userId\n", 
        FILE_APPEND
    );
    
    return $calls;
}

/**
 * Get count of user's filtered calls
 * 
 * @param int $userId User ID
 * @param array $filters Array of filters to apply
 * @return int Number of filtered calls
 */
function getUserCallsFilteredCount($userId, $filters) {
    $params = [$userId];
    $sql = "SELECT COUNT(*) as count FROM calls WHERE user_id = ?";
    
    // Apply filters
    if (!empty($filters['destination_number'])) {
        $sql .= " AND destination_number LIKE ?";
        $params[] = '%' . $filters['destination_number'] . '%';
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND started_at >= ?";
        $params[] = $filters['start_date'] . ' 00:00:00';
    }
    
    if (!empty($filters['end_date'])) {
        $sql .= " AND started_at <= ?";
        $params[] = $filters['end_date'] . ' 23:59:59';
    }
    
    $result = dbFetchRow($sql, $params);
    return $result ? (int)$result['count'] : 0;
} 