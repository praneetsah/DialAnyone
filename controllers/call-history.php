<?php
/**
 * Call History Controller
 */

// Page title
$pageTitle = 'Call History';

// Get user data
$userId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get user calls with pagination
$calls = getUserCalls($userId, $perPage, $offset);

// Get total calls for pagination
$totalCalls = getUserCallCount($userId);
$totalPages = ceil($totalCalls / $perPage);

// Get user call stats
$callStats = getUserCallStats($userId);

// Process call filter if submitted
$filterDestination = null;
$filterStatus = null;
$filterStartDate = null;
$filterEndDate = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    // Get filter values
    $filterDestination = sanitizeInput($_GET['destination'] ?? '');
    $filterStatus = sanitizeInput($_GET['status'] ?? '');
    $filterStartDate = sanitizeInput($_GET['start_date'] ?? '');
    $filterEndDate = sanitizeInput($_GET['end_date'] ?? '');
    
    // Apply filters
    $filters = [];
    
    if (!empty($filterDestination)) {
        $filters['destination_number'] = $filterDestination;
    }
    
    if (!empty($filterStatus) && in_array($filterStatus, ['completed', 'failed', 'in-progress'])) {
        $filters['status'] = $filterStatus;
    }
    
    if (!empty($filterStartDate)) {
        $filters['start_date'] = $filterStartDate;
    }
    
    if (!empty($filterEndDate)) {
        $filters['end_date'] = $filterEndDate;
    }
    
    // Get filtered calls with pagination
    $calls = getUserCallsFiltered($userId, $filters, $perPage, $offset);
    
    // Get total filtered calls for pagination
    $totalCalls = getUserCallsFilteredCount($userId, $filters);
    $totalPages = ceil($totalCalls / $perPage);
}

// Render view
renderView('call/history', [
    'pageTitle' => $pageTitle,
    'calls' => $calls,
    'callStats' => $callStats,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalCalls' => $totalCalls,
    'filterDestination' => $filterDestination,
    'filterStatus' => $filterStatus,
    'filterStartDate' => $filterStartDate,
    'filterEndDate' => $filterEndDate
]); 