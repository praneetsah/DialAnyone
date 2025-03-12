<?php
/**
 * Mail Configuration
 * 
 * Configuration for email sending via SMTP
 */

// SMTP Server Settings
// Option 1: cPanel/Direct Mail Server
define('SMTP_HOST', 'mail.example.com'); // Your domain mail server
define('SMTP_PORT', 587);                // Try port 587 (TLS) instead of 465 (SSL)
define('SMTP_USERNAME', 'support@example.com'); // Your email address
define('SMTP_PASSWORD', 'your_email_password');     // Your email password  
define('SMTP_SECURE', 'tls');            // Use 'tls' for port 587, 'ssl' for port 465

// Option 2: Gmail SMTP (commented out, uncomment to use)
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_USERNAME', 'your-gmail@gmail.com');
// define('SMTP_PASSWORD', 'your-app-password'); // Create this in Google account > Security
// define('SMTP_SECURE', 'tls');

// Option 3: Elastic Email (affordable third-party service, uncomment to use)
// define('SMTP_HOST', 'smtp.elasticemail.com');
// define('SMTP_PORT', 2525);
// define('SMTP_USERNAME', 'your-elastic-email-username');
// define('SMTP_PASSWORD', 'your-elastic-email-password');
// define('SMTP_SECURE', 'tls');

// Email Settings
define('EMAIL_FROM_NAME', 'Your App Name');    // Default sender name
define('EMAIL_FROM_ADDRESS', 'support@example.com'); // Default sender email

// Additional SMTP Options
define('SMTP_DEBUG', false);           // Set to true to enable verbose SMTP debugging
define('SMTP_TIMEOUT', 30);            // Connection timeout in seconds
define('SMTP_KEEP_ALIVE', false);      // Keep connection open for multiple emails
define('SMTP_VERIFY_PEER', true);      // SSL certificate verification

/**
 * IMPORTANT SMTP TROUBLESHOOTING
 * 
 * If you're having email sending issues, try these steps:
 * 
 * 1. Check your hosting provider - many shared hosts block outgoing SMTP connections
 * 2. Try different ports - common SMTP ports are 25, 465 (SSL), 587 (TLS), and 2525
 * 3. For cPanel hosting:
 *    - Log into cPanel > Email Accounts and verify your credentials
 *    - Make sure your hosting allows outgoing mail on the selected port
 * 
 * 4. If still having issues, use a third-party SMTP service like:
 *    - Elastic Email (very affordable)
 *    - SendGrid
 *    - Mailgun
 *    - Amazon SES
 */

/**
 * IMPORTANT: For Gmail SMTP
 * 
 * If you're using Gmail as your SMTP provider:
 * 
 * 1. Use smtp.gmail.com as your SMTP host
 * 2. Use port 587 with TLS security
 * 3. Use your Gmail address as the username
 * 4. For password, you MUST create an "App Password" in your Google account:
 *    - Go to your Google Account > Security > 2-Step Verification > App passwords
 *    - Create a new app password for "Mail" and use that as SMTP_PASSWORD
 *    - Regular Gmail passwords won't work due to Google's security policies
 * 
 * For other providers like SendGrid, Mailgun, etc., use their provided SMTP credentials
 */ 