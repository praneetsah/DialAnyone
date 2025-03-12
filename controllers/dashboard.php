<?php
/**
 * Dashboard Controller
 */

// Page title
$pageTitle = 'Dashboard';

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get user statistics
$callStats = getUserCallStats($userId);

// Get recent calls (limit to 5)
$recentCalls = getUserCalls($userId, 5, 0);

// Get recent payments (limit to 5)
$recentPayments = getUserPayments($userId, 5, 0);

// Auto top-up status
$autoTopUp = [
    'enabled' => $user['auto_topup'] == 1,
    'package' => $user['auto_topup'] ? getCreditPackageById($user['topup_package']) : null
];

// Check if credits are low (less than 100)
$creditsLow = $user['credits'] < 100;

// Render view
renderView('dashboard/index', [
    'pageTitle' => $pageTitle,
    'user' => $user,
    'callStats' => $callStats,
    'recentCalls' => $recentCalls,
    'recentPayments' => $recentPayments,
    'autoTopUp' => $autoTopUp,
    'creditsLow' => $creditsLow
]); 