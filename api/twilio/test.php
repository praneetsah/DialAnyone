<?php
/**
 * Simple Twilio Test File
 */

// Set content type for TwiML
header("Content-Type: text/xml");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Output simple XML directly without requiring any includes
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say>This is a test response from the Twilio test file.</Say></Response>';
} catch (Exception $e) {
    // Output error as XML
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say>An error occurred: ' . htmlspecialchars($e->getMessage()) . '</Say></Response>';
} 