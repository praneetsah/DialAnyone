<?php
/**
 * Call Debug Controller
 * 
 * For debugging call records in the database
 */

// Only allow admins to access this page
if (!isAdmin()) {
    redirect('index.php');
    exit;
}

// Page title
$pageTitle = 'Call Records Debug';

// Get database connection
require_once 'config/database.php';
$db = getDbConnection();

// Get all calls from database with all columns
$sql = "SELECT * FROM calls ORDER BY id DESC LIMIT 100";
$calls = dbFetchAll($sql);

// Get column information
$columnsSql = "SHOW COLUMNS FROM calls";
$columnsResult = dbFetchAll($columnsSql);
$columns = array_column($columnsResult, 'Field');

// Render view with full data
echo '<div class="container-fluid py-4">';
echo '<h2>Call Records Debug</h2>';
echo '<div class="alert alert-info">Displaying the latest 100 call records directly from the database, sorted by ID in descending order (newest first).</div>';

if (empty($calls)) {
    echo '<div class="alert alert-warning">No call records found in the database.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm table-bordered table-striped">';
    
    // Table header
    echo '<thead class="table-dark">';
    echo '<tr>';
    foreach ($columns as $column) {
        echo "<th>$column</th>";
    }
    echo '</tr>';
    echo '</thead>';
    
    // Table body
    echo '<tbody>';
    foreach ($calls as $call) {
        echo '<tr>';
        foreach ($columns as $column) {
            $value = isset($call[$column]) ? $call[$column] : 'NULL';
            if ($column === 'twilio_call_sid' && strlen($value) > 15) {
                $value = substr($value, 0, 15) . '...';
            }
            echo "<td>$value</td>";
        }
        echo '</tr>';
    }
    echo '</tbody>';
    
    echo '</table>';
    echo '</div>';
}
echo '</div>'; 