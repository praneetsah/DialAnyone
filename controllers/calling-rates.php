<?php
/**
 * Calling Rates Page Controller
 */

// Page title
$pageTitle = 'Calling Rates & Credit System';

// Page description for SEO
$pageDescription = 'Learn about our competitive calling rates and credit system for international calls. Make affordable calls worldwide with Dial Anyone.';

// Get credit packages for pricing section
$packages = getCreditPackages(true);

// Render view
renderView('calling-rates', [
    'pageTitle' => $pageTitle,
    'pageDescription' => $pageDescription,
    'packages' => $packages
]); 