<?php
/**
 * Terms of Service Controller
 */

// Set page title
$pageTitle = 'Terms of Service';

// Page description for SEO
$pageDescription = 'Read the Terms of Service for Dial Anyone. Our terms outline how to use our service responsibly for making international calls from your browser.';

// Render view
renderView('terms', [
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription
]); 