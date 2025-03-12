<?php
// Check if we already have the section parameter to prevent redirect loops
if (!isset($_GET['section'])) {
    // Only redirect if there's no section parameter
    header('Location: index.php?page=settings&section=profile');
    exit;
} else {
    // If section is specified but we're still on index.php, display profile view
    require_once __DIR__ . '/profile.php';
}
?> 