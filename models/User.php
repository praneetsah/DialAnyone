<?php
/**
 * User Model
 * 
 * User data management
 */

// Note: getUserById is already defined in includes/auth.php
// We don't need to redefine it here

/**
 * Get user by email
 * 
 * @param string $email User email
 * @return array|false User data or false if not found
 */
if (!function_exists('getUserByEmail')) {
    function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return dbFetchRow($sql, [$email]);
    }
}

/**
 * Get user by phone number
 * 
 * @param string $phoneNumber User phone number
 * @return array|false User data or false if not found
 */
if (!function_exists('getUserByPhoneNumber')) {
    function getUserByPhoneNumber($phoneNumber) {
        // Format phone number
        $phoneNumber = formatPhoneNumber($phoneNumber);
        
        $sql = "SELECT * FROM users WHERE phone_number = ?";
        return dbFetchRow($sql, [$phoneNumber]);
    }
}

/**
 * Get all users with pagination
 * 
 * @param int $limit Limit of records
 * @param int $offset Offset for pagination
 * @return array List of users
 */
if (!function_exists('getAllUsers')) {
    function getAllUsers($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?";
        return dbFetchAll($sql, [$limit, $offset]);
    }
}

/**
 * Get total user count
 * 
 * @return int Number of users
 */
if (!function_exists('getUserCount')) {
    function getUserCount() {
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = dbFetchRow($sql);
        return $result ? (int)$result['count'] : 0;
    }
}

/**
 * Get recent users
 * 
 * @param int $limit Limit of records
 * @return array List of recent users
 */
if (!function_exists('getRecentUsers')) {
    function getRecentUsers($limit = 10) {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT ?";
        return dbFetchAll($sql, [$limit]);
    }
}

/**
 * Add credits to user
 * 
 * @param int $userId User ID
 * @param float $amount Credit amount to add
 * @return bool Success or failure
 */
if (!function_exists('addUserCredits')) {
    function addUserCredits($userId, $amount) {
        // Get current credits
        $user = getUserById($userId);
        if (!$user) {
            return false;
        }
        
        // Calculate new credit balance
        $newBalance = $user['credits'] + $amount;
        
        // Update user
        $sql = "UPDATE users SET credits = ? WHERE id = ?";
        $success = dbQuery($sql, [$newBalance, $userId]);
        
        if ($success) {
            // Update session if this is the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_credits'] = $newBalance;
            }
            
            logPayment("Credits added: $amount to user", $userId, ['previous' => $user['credits'], 'new' => $newBalance]);
            return true;
        }
        
        return false;
    }
}

/**
 * Subtract credits from user
 * 
 * @param int $userId User ID
 * @param float $amount Credit amount to subtract
 * @return bool Success or failure
 */
if (!function_exists('subtractUserCredits')) {
    function subtractUserCredits($userId, $amount) {
        // Get current credits
        $user = getUserById($userId);
        if (!$user) {
            return false;
        }
        
        // Check if user has enough credits
        if ($user['credits'] < $amount) {
            return false;
        }
        
        // Calculate new credit balance
        $newBalance = $user['credits'] - $amount;
        
        // Update user
        $sql = "UPDATE users SET credits = ? WHERE id = ?";
        $success = dbQuery($sql, [$newBalance, $userId]);
        
        if ($success) {
            // Update session if this is the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_credits'] = $newBalance;
            }
            
            logPayment("Credits subtracted: $amount from user", $userId, ['previous' => $user['credits'], 'new' => $newBalance]);
            
            // Check if auto top-up should be triggered
            if ($newBalance < getSetting('min_credits_for_topup', 100) && $user['auto_topup'] == 1) {
                triggerAutoTopup($userId);
            }
            
            return true;
        }
        
        return false;
    }
}

/**
 * Trigger auto top-up for a user if enabled
 * 
 * @param int $userId User ID
 * @return bool|int Payment ID if created, false otherwise
 */
if (!function_exists('triggerAutoTopup')) {
    function triggerAutoTopup($userId) {
        // Get user
        $user = getUserById($userId);
        if (!$user || $user['auto_topup'] != 1) {
            return false;
        }
        
        // Get package ID for auto top-up
        $packageId = $user['topup_package'];
        
        // Get package details
        $sql = "SELECT * FROM credit_packages WHERE id = ?";
        $package = dbFetchRow($sql, [$packageId]);
        
        if (!$package) {
            // Default to first package
            $sql = "SELECT * FROM credit_packages ORDER BY price ASC LIMIT 1";
            $package = dbFetchRow($sql);
            
            if (!$package) {
                logError("Auto top-up failed: No packages found", 'payment');
                return false;
            }
        }
        
        // Create payment record
        $paymentId = dbInsert('payments', [
            'user_id' => $userId,
            'package_id' => $package['id'],
            'amount' => $package['price'],
            'credits' => $package['credits'],
            'status' => 'pending',
            'is_auto_topup' => 1
        ]);
        
        if (!$paymentId) {
            logError("Auto top-up failed: Could not create payment record", 'payment');
            return false;
        }
        
        // Process payment (this function would handle the actual payment processing)
        // In a real implementation, you would integrate with Stripe here
        // For now, we'll just mark it as successful
        $success = processAutoTopupPayment($paymentId);
        
        if ($success) {
            // Add credits to user
            addUserCredits($userId, $package['credits']);
            
            // Update payment status
            dbUpdate('payments', ['status' => 'completed'], 'id = ?', [$paymentId]);
            
            logPayment("Auto top-up completed for user", $userId, [
                'package_id' => $package['id'],
                'amount' => $package['price'],
                'credits' => $package['credits']
            ]);
            
            return true;
        }
        
        // Update payment status
        dbUpdate('payments', ['status' => 'failed'], 'id = ?', [$paymentId]);
        
        logPayment("Auto top-up failed for user", $userId, [
            'package_id' => $package['id'],
            'amount' => $package['price']
        ]);
        
        return false;
    }
}

/**
 * Process auto top-up payment after payment is completed
 * 
 * @param int $paymentId Payment ID
 * @return bool Success or failure
 */
if (!function_exists('processAutoTopupPayment')) {
    function processAutoTopupPayment($paymentId) {
        // This function would handle the actual payment processing with Stripe
        // For now, we'll just return true for demonstration
        // In a real implementation, you would use the stored payment method to charge the user
        
        // For demonstration purposes, we'll just mark it as successful
        return true;
    }
}

/**
 * Search users
 * 
 * @param string $query Search query
 * @param int $limit Limit of records
 * @return array List of users matching search
 */
if (!function_exists('searchUsers')) {
    function searchUsers($query, $limit = 100) {
        $query = '%' . $query . '%';
        
        $sql = "SELECT * FROM users 
                WHERE name LIKE ? OR email LIKE ? OR phone_number LIKE ? 
                ORDER BY id DESC LIMIT ?";
        
        return dbFetchAll($sql, [$query, $query, $query, $limit]);
    }
}

/**
 * Ban a user
 * 
 * @param int $userId User ID
 * @return bool Success or failure
 */
if (!function_exists('banUser')) {
    function banUser($userId) {
        // In a real implementation, you would have a 'banned' field in the users table
        // For now, we'll just log the action
        logInfo("User banned: $userId", 'admin');
        return true;
    }
}

/**
 * Check if user exists
 * 
 * @param int $userId User ID
 * @return bool User exists or not
 */
if (!function_exists('userExists')) {
    function userExists($userId) {
        $sql = "SELECT id FROM users WHERE id = ?";
        $result = dbFetchRow($sql, [$userId]);
        
        return $result ? true : false;
    }
}

/**
 * Create a new user
 * 
 * @param array $userData User data
 * @return int|false User ID or false on failure
 */
if (!function_exists('createUser')) {
    function createUser($userData) {
        // Validate required fields
        if (empty($userData['name']) || empty($userData['email']) || empty($userData['phone']) || empty($userData['password'])) {
            return false;
        }
        
        // Format phone number
        $phoneNumber = formatPhoneNumber($userData['phone']);
        
        // Check if email already exists
        if (getUserByEmail($userData['email'])) {
            return false;
        }
        
        // Check if phone already exists
        if (getUserByPhoneNumber($phoneNumber)) {
            return false;
        }
        
        // Generate verification code
        $verificationCode = generateVerificationCode();
        
        // Prepare user data (password is already hashed in controller)
        $user = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone_number' => $phoneNumber,
            'password' => $userData['password'], // Password comes pre-hashed from controller
            'credits' => WELCOME_CREDITS,
            'is_phone_verified' => 0,
            'is_email_verified' => 0,
            'verification_code' => $verificationCode,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert user
        $userId = dbInsert('users', $user);
        
        if ($userId) {
            // Send verification code via email
            if (function_exists('sendVerificationEmail')) {
                $success = sendVerificationEmail($userData['email'], $verificationCode);
                if (!$success) {
                    logMessage("Failed to send verification email to: {$userData['email']}", 'error');
                }
            }
            
            logMessage("User created successfully: {$userData['email']}", 'info');
            return $userId;
        }
        
        return false;
    }
}

/**
 * Get total number of users
 * 
 * @return int Total users
 */
function getTotalUsers() {
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = dbFetchRow($sql);
    
    return $result ? (int)$result['total'] : 0;
}

/**
 * Get total number of verified users
 * 
 * @return int Total verified users
 */
function getTotalVerifiedUsers() {
    $sql = "SELECT COUNT(*) as total FROM users WHERE is_phone_verified = 1";
    $result = dbFetchRow($sql);
    
    return $result ? (int)$result['total'] : 0;
}

/**
 * Get total credits in the system
 * 
 * @return float Total credits
 */
function getTotalCreditsInSystem() {
    $sql = "SELECT SUM(credits) as total FROM users";
    $result = dbFetchRow($sql);
    
    return $result ? (float)$result['total'] : 0;
} 