<?php
/**
 * Privacy Policy Controller
 */

// Set page title
$pageTitle = 'Privacy Policy';

// Page description for SEO
$pageDescription = 'Read the Privacy Policy for Dial Anyone. Learn how we protect your data when you use our browser-based international calling service.';

// Render view
renderView('privacy', [
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription
]); 