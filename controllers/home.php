<?php
/**
 * Home Page Controller
 */

// Page title
$pageTitle = 'Make Cheap International Calls From Your Browser';

// Page description for SEO
$pageDescription = 'Dial Anyone lets you make cheap international calls to any country directly from your web browser. No apps required. Get 1000 credits for just 400 minutes of worldwide calling!';

// Get credit packages for pricing section
$packages = getCreditPackages(true);

// Render view
renderView('home', [
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription,
    'packages' => $packages
]); 