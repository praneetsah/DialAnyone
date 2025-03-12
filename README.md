# DialAnyone - Source Available Phone Calling Platform

DialAnyone is a source-available phone calling platform that allows users to make calls from their browser to any phone number worldwide. This project uses Twilio for telephony services and Stripe for payment processing.

## Important License Information

**This code is provided for viewing and educational purposes ONLY.**

You are NOT permitted to copy, modify, or reuse any part of this code in any other projects, whether for commercial or non-commercial purposes. Please see the LICENSE file for full details.

## Features

- Make phone calls directly from your browser to any phone number
- User registration and authentication system
- Credit-based calling system
- Secure payment processing with Stripe
- Call history and user dashboard
- Admin panel for managing users and calls
- Responsive design that works on desktop and mobile

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer for PHP dependencies
- Twilio account for telephony services
- Stripe account for payment processing
- Web server (Apache or Nginx)

## Code Structure Overview

This repository contains the source code for educational purposes. The key components include:

- `config/` - Configuration files with placeholder values for sensitive information
- `controllers/` - Application logic organized by feature
- `models/` - Data models for users, calls, payments, etc.
- `views/` - UI templates for the frontend
- `api/` - API endpoints for various services
- `db/` - Database schema and setup scripts
- `includes/` - Helper functions and shared code
- `assets/` - Frontend assets (CSS, JavaScript, images)

## Security Considerations

If you're studying this code for educational purposes, note these security best practices:

- Always use HTTPS in production systems
- Keep API keys and credentials secure
- Regularly update dependencies
- Follow best practices for PHP and web security

## License

This project is licensed under a custom viewing-only license - see the LICENSE file for details. You may view and study the code, but you are not permitted to reuse it for any purpose.

## Acknowledgments

- [Twilio](https://www.twilio.com/) for telephony services
- [Stripe](https://stripe.com/) for payment processing
- [Bootstrap](https://getbootstrap.com/) for frontend framework

## Installation

1. Clone this repository to your web server:
   ```
   git clone https://github.com/yourusername/dialanyone.git
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Create a MySQL database and import the schema:
   ```
   mysql -u username -p your_database_name < db/schema.sql
   ```

4. Configure your environment:
   - Update `config/database.php` with your database credentials
   - Update `config/twilio.php` with your Twilio API credentials
   - Update `config/stripe.php` with your Stripe API credentials
   - Update `config/mail.php` with your SMTP server details
   - Update `config/app.php` with your application settings

5. Set appropriate permissions:
   ```
   chmod -R 755 .
   chmod -R 777 logs cache
   ```

6. Set up a virtual host pointing to the project's root directory

## Configuration

### Twilio Configuration

You need to set up a Twilio account and obtain the following credentials:
- Account SID
- Auth Token
- Twilio Phone Number
- TwiML App SID

Update these in `config/twilio.php`.

### Stripe Configuration

You need to set up a Stripe account and obtain the following credentials:
- Secret Key
- Publishable Key
- Webhook Secret

Update these in `config/stripe.php`.

### Email Configuration

Configure your SMTP settings in `config/mail.php`. You can use your own mail server or services like Gmail, SendGrid, or Elastic Email.

## Usage

1. Register a new user account
2. Purchase credits using Stripe
3. Make calls to any phone number using your credits
4. View your call history and manage your account 