<?php
/**
 * Payment History Controller
 */

// Page title
$pageTitle = 'Payment History';

// Get user data
$userId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get user payments with pagination
$payments = getUserPayments($userId, $perPage, $offset);

// Get total payments for pagination
$totalPayments = getUserPaymentCount($userId);
$totalPages = ceil($totalPayments / $perPage);

// Process payment filter if submitted
$filterPackage = null;
$filterStatus = null;
$filterStartDate = null;
$filterEndDate = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    // Get filter values
    $filterPackage = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;
    $filterStatus = sanitizeInput($_GET['status'] ?? '');
    $filterStartDate = sanitizeInput($_GET['start_date'] ?? '');
    $filterEndDate = sanitizeInput($_GET['end_date'] ?? '');
    
    // Apply filters
    $filters = [];
    
    if ($filterPackage) {
        $filters['package_id'] = $filterPackage;
    }
    
    if (!empty($filterStatus) && in_array($filterStatus, ['completed', 'pending', 'failed'])) {
        $filters['status'] = $filterStatus;
    }
    
    if (!empty($filterStartDate)) {
        $filters['start_date'] = $filterStartDate;
    }
    
    if (!empty($filterEndDate)) {
        $filters['end_date'] = $filterEndDate;
    }
    
    // Note: This would require implementing the filtered payment functions
    // For now, we'll just use the basic getUserPayments function
    
    // Get filtered payments with pagination (if these functions existed)
    // $payments = getUserPaymentsFiltered($userId, $filters, $perPage, $offset);
    // $totalPayments = getUserPaymentsFilteredCount($userId, $filters);
    // $totalPages = ceil($totalPayments / $perPage);
}

// Get credit packages for filter dropdown
$creditPackages = getCreditPackages();

// Render view
renderView('payment/history', [
    'pageTitle' => $pageTitle,
    'payments' => $payments,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalPayments' => $totalPayments,
    'filterPackage' => $filterPackage,
    'filterStatus' => $filterStatus,
    'filterStartDate' => $filterStartDate,
    'filterEndDate' => $filterEndDate,
    'creditPackages' => $creditPackages
]); 