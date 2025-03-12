<?php
/**
 * Settings Controller
 */

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log any errors that occur
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorMessage = "Error [$errno] $errstr in $errfile on line $errline";
    error_log($errorMessage, 3, __DIR__ . '/../logs/settings-errors.log');
    
    // Continue execution
    return true;
});

// Default to profile section if none specified
$section = $_GET['section'] ?? 'profile';

// Set page title based on section
switch ($section) {
    case 'password':
        $pageTitle = 'Change Password';
        break;
    case 'billing':
        $pageTitle = 'Billing Settings';
        break;
    case 'notifications':
        $pageTitle = 'Notification Preferences';
        break;
    case 'profile':
    default:
        $pageTitle = 'Profile Information';
        $section = 'profile'; // Ensure section is 'profile' for default case
        break;
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get credit packages for auto top-up (only needed for billing section)
$creditPackages = ($section === 'billing') ? getCreditPackages() : [];

// Initialize success and error messages
$updateSuccess = false;
$updateError = null;
$passwordSuccess = false;
$passwordError = null;
$notificationSuccess = false;
$notificationError = null;
$billingSuccess = false;
$billingError = null;

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'update_profile') {
            $updateError = 'Invalid form submission, please try again';
        } elseif ($_POST['action'] === 'update_password') {
            $passwordError = 'Invalid form submission, please try again';
        } elseif ($_POST['action'] === 'update_notifications') {
            $notificationError = 'Invalid form submission, please try again';
        } elseif ($_POST['action'] === 'update_auto_topup') {
            $billingError = 'Invalid form submission, please try again';
        }
    } else {
        // Process based on action type
        $action = $_POST['action'];
        
        // Update profile information
        if ($action === 'update_profile') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            
            // Validation
            if (empty($name)) {
                $updateError = 'Name is required';
            } elseif (empty($email)) {
                $updateError = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $updateError = 'Please enter a valid email address';
            } 
            // Check if user is trying to change verified email
            elseif ($user['is_email_verified'] && $email !== $user['email']) {
                $updateError = 'Email address cannot be changed once verified.';
            }
            else {
                // Update user data
                $userData = [
                    'name' => $name,
                    'email' => $email,
                ];
                
                $updated = updateUser($userId, $userData);
                
                if ($updated) {
                    // Refresh user data
                    $user = getUserById($userId);
                    $updateSuccess = true;
                    
                    // Log activity
                    logUserActivity($userId, 'Profile updated');
                } else {
                    $updateError = 'Failed to update profile, please try again';
                }
            }
        }
        
        // Update password
        if ($action === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($currentPassword)) {
                $passwordError = 'Current password is required';
            } elseif (empty($newPassword)) {
                $passwordError = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $passwordError = 'New password must be at least 8 characters long';
            } elseif ($newPassword !== $confirmPassword) {
                $passwordError = 'New passwords do not match';
            } else {
                // Verify current password
                if (!verifyPassword($currentPassword, $user['password'])) {
                    $passwordError = 'Current password is incorrect';
                } else {
                    // Update password
                    $updated = updateUser($userId, [
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT)
                    ]);
                    
                    if ($updated) {
                        // Log password update
                        logUserActivity($userId, 'Password updated');
                        
                        // Set success message
                        $passwordSuccess = true;
                    } else {
                        $passwordError = 'Failed to update password, please try again';
                    }
                }
            }
        }
        
        // Update notification preferences
        if ($action === 'update_notifications') {
            // Get notification settings from POST data
            $notifications = $_POST['notifications'] ?? [];
            
            // Set defaults for missing options
            $notificationSettings = [
                'email_low_credits' => isset($notifications['email_low_credits']) ? true : false,
                'email_payment_confirmation' => isset($notifications['email_payment_confirmation']) ? true : false,
                'email_account_activity' => isset($notifications['email_account_activity']) ? true : false,
                'email_marketing' => isset($notifications['email_marketing']) ? true : false,
                'sms_low_credits' => isset($notifications['sms_low_credits']) ? true : false,
                'sms_payment_confirmation' => isset($notifications['sms_payment_confirmation']) ? true : false
            ];
            
            // Update user data with notification preferences
            $userData = [
                'notification_settings' => json_encode($notificationSettings)
            ];
            
            $updated = updateUser($userId, $userData);
            
            if ($updated) {
                // Refresh user data
                $user = getUserById($userId);
                $notificationSuccess = true;
                
                // Log activity
                logUserActivity($userId, 'Notification settings updated');
            } else {
                $notificationError = 'Failed to update notification settings, please try again';
            }
        }
        
        // Update auto top-up settings
        if ($action === 'update_auto_topup') {
            $enableAutoTopup = isset($_POST['enable_auto_topup']) ? 1 : 0;
            $topupPackage = $enableAutoTopup ? (int)($_POST['topup_package'] ?? 0) : 0;
            
            // Update auto top-up settings
            $updated = updateUser($userId, [
                'auto_topup' => $enableAutoTopup,
                'topup_package' => $topupPackage
            ]);
            
            if ($updated) {
                // Log update
                logUserActivity($userId, 'Auto top-up settings updated');
                
                // Set success message and refresh user data
                $billingSuccess = true;
                $user = getUserById($userId);
            } else {
                $billingError = 'Failed to update auto top-up settings, please try again';
            }
        }
    }
}

// Check if view file exists
$viewFile = __DIR__ . '/../views/settings/' . $section . '.php';
if (file_exists($viewFile)) {
    // Render view based on section
    renderView('settings/' . $section, [
        'pageTitle' => $pageTitle,
        'user' => $user,
        'creditPackages' => $creditPackages,
        'updateSuccess' => $updateSuccess,
        'updateError' => $updateError,
        'passwordSuccess' => $passwordSuccess,
        'passwordError' => $passwordError,
        'notificationSuccess' => $notificationSuccess,
        'notificationError' => $notificationError,
        'billingSuccess' => $billingSuccess,
        'billingError' => $billingError
    ]);
} else {
    // If section view doesn't exist, default to profile
    renderView('settings/profile', [
        'pageTitle' => 'Profile Information',
        'user' => $user,
        'updateSuccess' => $updateSuccess,
        'updateError' => $updateError
    ]);
} 