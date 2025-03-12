<?php
/**
 * Application Configuration
 * 
 * This file contains general application settings and constants
 */

// Site URL - Change this to your actual site URL
define('SITE_URL', 'https://example.com');

// Site name - Used in emails and other places
define('SITE_NAME', 'Your App Name');

// Support email - Used for sending emails
define('SUPPORT_EMAIL', 'support@example.com');

// User settings
define('WELCOME_CREDITS', 10); // Credits given to new users upon registration

// Call settings
define('MIN_CREDITS_FOR_CALL', 1); // Minimum credits required to make a call
define('CREDITS_PER_MINUTE', 1); // Credits consumed per minute of call

// Auto top-up settings
define('MIN_CREDITS_FOR_TOPUP', 5); // Minimum credits threshold to trigger auto top-up

// Pagination settings
define('ITEMS_PER_PAGE', 10); // Number of items to display per page 