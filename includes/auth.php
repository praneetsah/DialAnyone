<?php
/**
 * Authentication Functions
 * 
 * This file contains functions for user authentication
 */

/**
 * Register a new user
 * 
 * @param string $name User name
 * @param string $email User email
 * @param string $phoneNumber User phone number
 * @param string $password User password
 * @return int|false User ID or false on failure
 */
function registerUser($name, $email, $phoneNumber, $password) {
    // Format phone number
    $phoneNumber = formatPhoneNumber($phoneNumber);
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $result = dbFetchRow($sql, [$email]);
    if ($result) {
        logMessage("Registration failed: Email already exists - $email", 'error');
        return false;
    }
    
    // Check if phone number already exists
    $sql = "SELECT id FROM users WHERE phone_number = ?";
    $result = dbFetchRow($sql, [$phoneNumber]);
    if ($result) {
        logMessage("Registration failed: Phone number already exists - $phoneNumber", 'error');
        return false;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification code
    $verificationCode = generateVerificationCode();
    
    // Insert user
    $userId = dbInsert('users', [
        'name' => $name,
        'email' => $email,
        'phone_number' => $phoneNumber,
        'password' => $hashedPassword,
        'verification_code' => $verificationCode
    ]);
    
    if ($userId) {
        logMessage("User registered successfully: $email", 'info');
        
        // Send verification code
        $success = sendVerificationSMS($phoneNumber, $verificationCode);
        if (!$success) {
            logMessage("Failed to send verification SMS to: $phoneNumber", 'error');
        }
        
        return $userId;
    }
    
    logMessage("Registration failed: Database error", 'error');
    return false;
}

/**
 * Authenticate user with email and password
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data or false on failure
 */
function loginUser($email, $password) {
    // Get user by email
    $sql = "SELECT * FROM users WHERE email = ?";
    $user = dbFetchRow($sql, [$email]);
    
    if (!$user) {
        logMessage("Login failed: User not found - $email", 'error');
        return false;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        logMessage("Login failed: Invalid password for user - $email", 'error');
        return false;
    }
    
    // Log successful login
    logMessage("User logged in: $email", 'info');
    
    // Return user data
    return $user;
}

/**
 * Set user session data
 * 
 * @param array $user User data
 * @return void
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['is_phone_verified'] = $user['is_phone_verified'];
    $_SESSION['is_email_verified'] = $user['is_email_verified'] ?? 0;
    $_SESSION['user_credits'] = $user['credits'];
}

/**
 * Clear user session data
 * 
 * @return void
 */
function logoutUser() {
    // Log logout
    if (isset($_SESSION['user_email'])) {
        logMessage("User logged out: " . $_SESSION['user_email'], 'info');
    }
    
    // Clear session data
    session_unset();
    session_destroy();
}

/**
 * Verify phone number with verification code
 * 
 * @param int $userId User ID
 * @param string $code Verification code
 * @return bool Success or failure
 */
function verifyPhoneNumber($userId, $code) {
    // Get user verification code
    $sql = "SELECT verification_code FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result || !isset($result['verification_code'])) {
        logMessage("Phone verification failed: User not found - $userId", 'error');
        return false;
    }
    
    // Verify code
    if ($result['verification_code'] !== $code) {
        logMessage("Phone verification failed: Invalid code for user - $userId", 'error');
        return false;
    }
    
    // Update user
    $success = dbUpdate('users', [
        'is_phone_verified' => 1,
        'verification_code' => null
    ], 'id = ?', [$userId]);
    
    if ($success) {
        // Update session
        $_SESSION['is_phone_verified'] = 1;
        
        logMessage("Phone verified successfully for user - $userId", 'info');
        return true;
    }
    
    logMessage("Phone verification failed: Database error", 'error');
    return false;
}

/**
 * Reset user verification code and send new one
 * 
 * @param int $userId User ID
 * @return bool Success or failure
 */
function resetVerificationCode($userId) {
    // Get user phone number
    $sql = "SELECT phone_number FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result || !isset($result['phone_number'])) {
        logMessage("Reset verification code failed: User not found - $userId", 'error');
        return false;
    }
    
    // Generate new verification code
    $verificationCode = generateVerificationCode();
    
    // Update user
    $success = dbUpdate('users', [
        'verification_code' => $verificationCode,
        'is_phone_verified' => 0
    ], 'id = ?', [$userId]);
    
    if ($success) {
        // Send verification code
        $success = sendVerificationSMS($result['phone_number'], $verificationCode);
        if (!$success) {
            logMessage("Failed to send verification SMS to: " . $result['phone_number'], 'error');
            return false;
        }
        
        // Update session
        $_SESSION['is_phone_verified'] = 0;
        
        logMessage("Verification code reset for user - $userId", 'info');
        return true;
    }
    
    logMessage("Reset verification code failed: Database error", 'error');
    return false;
}

/**
 * Check if user has verified phone number
 * 
 * @param int $userId User ID
 * @return bool Is verified
 */
function isPhoneVerified($userId) {
    // Get user verification status
    $sql = "SELECT is_phone_verified FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result) {
        return false;
    }
    
    return $result['is_phone_verified'] == 1;
}

/**
 * Get user data by ID
 * 
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    return dbFetchRow($sql, [$userId]);
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $data Profile data
 * @return bool Success or failure
 */
function updateUserProfile($userId, $data) {
    // Validate user ID
    if (!$userId) {
        return false;
    }
    
    // Update user
    $success = dbUpdate('users', $data, 'id = ?', [$userId]);
    
    if ($success) {
        logMessage("Profile updated for user - $userId", 'info');
        return true;
    }
    
    logMessage("Profile update failed for user - $userId", 'error');
    return false;
}

/**
 * Change user password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return bool Success or failure
 */
function changeUserPassword($userId, $currentPassword, $newPassword) {
    // Get user data
    $sql = "SELECT password FROM users WHERE id = ?";
    $user = dbFetchRow($sql, [$userId]);
    
    if (!$user) {
        logMessage("Password change failed: User not found - $userId", 'error');
        return false;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        logMessage("Password change failed: Invalid current password for user - $userId", 'error');
        return false;
    }
    
    // Hash new password
    $hashedPassword = password_verify($newPassword, $user['password']) ? $user['password'] : password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update user
    $success = dbUpdate('users', [
        'password' => $hashedPassword
    ], 'id = ?', [$userId]);
    
    if ($success) {
        logMessage("Password changed for user - $userId", 'info');
        return true;
    }
    
    logMessage("Password change failed: Database error", 'error');
    return false;
}

/**
 * Enable or disable auto top-up for user
 * 
 * @param int $userId User ID
 * @param bool $enabled Enable or disable
 * @param int $packageId Package ID for auto top-up
 * @return bool Success or failure
 */
function updateAutoTopup($userId, $enabled, $packageId = null) {
    $data = [
        'auto_topup' => $enabled ? 1 : 0
    ];
    
    if ($packageId) {
        $data['topup_package'] = $packageId;
    }
    
    // Update user
    $success = dbUpdate('users', $data, 'id = ?', [$userId]);
    
    if ($success) {
        logMessage("Auto top-up " . ($enabled ? "enabled" : "disabled") . " for user - $userId", 'info');
        return true;
    }
    
    logMessage("Auto top-up update failed for user - $userId", 'error');
    return false;
}

/**
 * Check if email exists in the database
 * 
 * @param string $email Email to check
 * @return bool True if email exists, false otherwise
 */
function isEmailExists($email) {
    $sql = "SELECT id FROM users WHERE email = ?";
    $result = dbFetchRow($sql, [$email]);
    return $result ? true : false;
}

/**
 * Check if phone number exists in the database
 * 
 * @param string $phoneNumber Phone number to check
 * @return bool True if phone number exists, false otherwise
 */
function isPhoneExists($phoneNumber) {
    // Format phone number
    $phoneNumber = formatPhoneNumber($phoneNumber);
    
    $sql = "SELECT id FROM users WHERE phone_number = ?";
    $result = dbFetchRow($sql, [$phoneNumber]);
    return $result ? true : false;
}

/**
 * Store a password reset token in the database
 * 
 * @param int $userId User ID
 * @param string $token Reset token
 * @param string $expiryDate Token expiry date (Y-m-d H:i:s format)
 * @return bool True on success, false on failure
 */
function storePasswordResetToken($userId, $token, $expiryDate) {
    // Check if there's an existing token for this user and delete it
    $sql = "DELETE FROM verification_codes WHERE user_id = ? AND type = 'password_reset'";
    dbQuery($sql, [$userId]);
    
    // Store new token
    $data = [
        'user_id' => $userId,
        'code' => $token,
        'type' => 'password_reset',
        'expires_at' => $expiryDate,
        'is_used' => 0
    ];
    
    $result = dbInsert('verification_codes', $data);
    return $result !== false;
}

/**
 * Get password reset token data
 * 
 * @param string $token Reset token
 * @return array|false Token data or false if not found
 */
function getPasswordResetToken($token) {
    $sql = "SELECT * FROM verification_codes WHERE code = ? AND type = 'password_reset' AND is_used = 0";
    return dbFetchRow($sql, [$token]);
}

/**
 * Check if a token has expired
 * 
 * @param string $expiryDate Token expiry date (Y-m-d H:i:s format)
 * @return bool True if expired, false otherwise
 */
function isTokenExpired($expiryDate) {
    $now = new DateTime();
    $expiry = new DateTime($expiryDate);
    return $now > $expiry;
}

/**
 * Invalidate a password reset token after use
 * 
 * @param string $token Reset token
 * @return bool True on success, false on failure
 */
function invalidatePasswordResetToken($token) {
    $sql = "UPDATE verification_codes SET is_used = 1 WHERE code = ? AND type = 'password_reset'";
    return dbQuery($sql, [$token]) !== false;
}

/**
 * Update user password
 * 
 * @param int $userId User ID
 * @param string $hashedPassword Hashed password
 * @return bool True on success, false on failure
 */
function updateUserPassword($userId, $hashedPassword) {
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    return dbQuery($sql, [$hashedPassword, $userId]) !== false;
}

/**
 * Update user data
 * 
 * @param int $userId User ID
 * @param array $data Associative array of user data to update
 * @return bool True if successful, false otherwise
 */
function updateUser($userId, $data) {
    try {
        // Check if columns exist and add them if not
        ensureUserColumns(['auto_topup', 'topup_package', 'notification_settings']);
        
        $db = getDbConnection();
        
        $setClauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setClauses[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        
        $setClause = implode(', ', $setClauses);
        
        $query = "UPDATE users SET $setClause WHERE id = :user_id";
        $params[':user_id'] = $userId;
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);
        
        // Log the operation
        $logMessage = "Updated user data: " . implode(', ', array_keys($data));
        logToFile('database-log.txt', $logMessage);
        
        return $result;
    } catch (PDOException $e) {
        // Log error
        logError('Database error updating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ensure required columns exist in the users table
 * 
 * @param array $columns Array of column names to check/add
 * @return bool True if successful
 */
function ensureUserColumns($columns) {
    try {
        $db = getDbConnection();
        
        // Get current column information
        $stmt = $db->query("SHOW COLUMNS FROM users");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        foreach ($columns as $column) {
            if (!in_array($column, $existingColumns)) {
                switch ($column) {
                    case 'auto_topup':
                        $db->exec("ALTER TABLE users ADD COLUMN auto_topup TINYINT(1) NOT NULL DEFAULT 0");
                        logToFile('database-log.txt', "Added column 'auto_topup' to users table");
                        break;
                    case 'topup_package':
                        $db->exec("ALTER TABLE users ADD COLUMN topup_package INT NOT NULL DEFAULT 0");
                        logToFile('database-log.txt', "Added column 'topup_package' to users table");
                        break;
                    case 'notification_settings':
                        $db->exec("ALTER TABLE users ADD COLUMN notification_settings TEXT NULL");
                        logToFile('database-log.txt', "Added column 'notification_settings' to users table");
                        break;
                }
            }
        }
        
        return true;
    } catch (PDOException $e) {
        logError('Database error ensuring columns: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify if a plaintext password matches a hashed password
 * 
 * @param string $plainPassword Plaintext password to verify
 * @param string $hashedPassword Stored hashed password
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($plainPassword, $hashedPassword) {
    return password_verify($plainPassword, $hashedPassword);
}

/**
 * Log user activity
 * 
 * @param int $userId User ID
 * @param string $action Action performed
 * @param array $data Additional data
 * @return bool True on success, false on failure
 */
function logUserActivity($userId, $action, $data = []) {
    try {
        // Get user's IP address
        $ip = getClientIp();
        
        // Insert into user_activity table
        $activityData = [
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ip,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add additional data if provided
        if (!empty($data)) {
            $activityData['data'] = json_encode($data);
        }
        
        // Log to file
        $logMessage = "User $userId performed action: $action from IP: $ip";
        logToFile('user-activity.log', $logMessage, $data);
        
        // Insert into database if table exists
        try {
            $db = getDbConnection();
            $stmt = $db->query("SHOW TABLES LIKE 'user_activity'");
            if ($stmt->rowCount() > 0) {
                dbInsert('user_activity', $activityData);
            }
        } catch (Exception $e) {
            // If table doesn't exist, just continue with file logging
            logError("Could not log to database: " . $e->getMessage());
        }
        
        return true;
    } catch (Exception $e) {
        logError("Error logging user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify email with verification code
 * 
 * @param int $userId User ID
 * @param string $code Verification code
 * @return bool Success or failure
 */
function verifyEmail($userId, $code) {
    // Get user verification code
    $sql = "SELECT verification_code FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result || !isset($result['verification_code'])) {
        logMessage("Email verification failed: User not found - $userId", 'error');
        return false;
    }
    
    // Verify code
    if ($result['verification_code'] !== $code) {
        logMessage("Email verification failed: Invalid code for user - $userId", 'error');
        return false;
    }
    
    // Update user
    $success = dbUpdate('users', [
        'is_email_verified' => 1,
        'verification_code' => null
    ], 'id = ?', [$userId]);
    
    if ($success) {
        // Update session
        $_SESSION['is_email_verified'] = 1;
        
        logMessage("Email verified successfully for user - $userId", 'info');
        return true;
    }
    
    logMessage("Email verification failed: Database error", 'error');
    return false;
}

/**
 * Reset user email verification code and send new one
 * 
 * @param int $userId User ID
 * @return bool Success or failure
 */
function resetEmailVerificationCode($userId) {
    // Get user email
    $sql = "SELECT email FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result || !isset($result['email'])) {
        logMessage("Reset email verification code failed: User not found - $userId", 'error');
        return false;
    }
    
    // Generate new verification code
    $verificationCode = generateVerificationCode();
    
    // Update user
    $success = dbUpdate('users', [
        'verification_code' => $verificationCode,
        'is_email_verified' => 0
    ], 'id = ?', [$userId]);
    
    if ($success) {
        // Send verification code
        $success = sendVerificationEmail($result['email'], $verificationCode);
        if (!$success) {
            logMessage("Failed to send verification email to: " . $result['email'], 'error');
            return false;
        }
        
        // Update session
        $_SESSION['is_email_verified'] = 0;
        
        return true;
    }
    
    return false;
}

/**
 * Check if user email is verified
 * 
 * @param int $userId User ID
 * @return bool True if verified, false otherwise
 */
function isEmailVerified($userId) {
    $sql = "SELECT is_email_verified FROM users WHERE id = ?";
    $result = dbFetchRow($sql, [$userId]);
    
    if (!$result) {
        return false;
    }
    
    return (bool)$result['is_email_verified'];
}

/**
 * Check if user needs verification
 * 
 * @return bool True if verification is needed
 */
function needsVerification() {
    // Check if logged in
    if (!isAuthenticated()) {
        return false;
    }
    
    // Get current page
    $currentPage = $_GET['page'] ?? '';
    
    // Only require verification for call page
    if ($currentPage !== 'call') {
        return false;
    }
    
    // Get user ID
    $userId = $_SESSION['user_id'] ?? 0;
    if (!$userId) {
        return false;
    }
    
    // Check if verification is required in session
    if (isset($_SESSION['is_email_verified']) && $_SESSION['is_email_verified']) {
        return false;
    }
    
    // If not set in session, check database directly
    if (!isset($_SESSION['is_email_verified'])) {
        // Check database
        if (isEmailVerified($userId)) {
            // Update session
            $_SESSION['is_email_verified'] = 1;
            return false;
        }
        // Not verified in database
        $_SESSION['is_email_verified'] = 0;
    }
    
    return true;
} 