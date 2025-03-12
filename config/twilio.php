<?php
/**
 * Twilio Configuration
 * 
 * This file contains Twilio API credentials and helper functions
 */

// Twilio API credentials - Change these to your actual Twilio credentials
define('TWILIO_ACCOUNT_SID', 'your_twilio_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token');
define('TWILIO_APP_SID', 'your_twilio_app_sid'); // TwiML AppSID for Client calls
define('TWILIO_PHONE_NUMBER', 'your_twilio_phone_number'); // Your Twilio phone number

// Twilio API Key and Secret
// If not defined, the code will fallback to using account SID and auth token
// To create API keys, go to https://www.twilio.com/console/project/api-keys
define('TWILIO_API_KEY', 'your_twilio_api_key'); // Replace with your API Key SID
define('TWILIO_API_SECRET', 'your_twilio_api_secret'); // Replace with your API Secret

// Initialize Twilio Client
function getTwilioClient() {
    // Ensure Twilio SDK is loaded
    $autoloadPaths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php'
    ];
    
    $loaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }
    
    // Check if Twilio library is installed
    if (!$loaded || !class_exists('Twilio\Rest\Client')) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . ' - Twilio library not found. Please install the Twilio PHP SDK.' . "\n", 
            FILE_APPEND
        );
        return null;
    }
    
    try {
        // Create Twilio client
        $client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
        
        return $client;
    } catch (\Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . ' - Twilio client error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return null;
    }
}

// Generate a token for Twilio Client
function generateTwilioToken($identity) {
    // Ensure Twilio SDK is loaded
    $autoloadPaths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php'
    ];
    
    $loaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }
    
    if (!$loaded || !class_exists('Twilio\Jwt\AccessToken')) {
        return null;
    }
    
    try {
        // Create access token, which we will serialize and send to the client
        $token = new Twilio\Jwt\AccessToken(
            TWILIO_ACCOUNT_SID,
            TWILIO_API_KEY,
            TWILIO_API_SECRET,
            3600,
            $identity
        );
        
        // Create Voice grant
        $voiceGrant = new Twilio\Jwt\Grants\VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid(TWILIO_APP_SID);
        
        // Optional: add incoming capabilities
        $voiceGrant->setIncomingAllow(true);
        
        // Add grant to token
        $token->addGrant($voiceGrant);
        
        // Render token to string
        return $token->toJWT();
    } catch (\Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . ' - Token generation error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        return null;
    }
}

// Generate random verification code
function generateVerificationCode($length = 6) {
    // Generate a random numeric code
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Send verification SMS
function sendVerificationSMS($phoneNumber, $code) {
    try {
        // Get Twilio client
        $client = getTwilioClient();
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Failed to initialize Twilio client'
            ];
        }
        
        // Format the message
        $message = "Your verification code is: $code";
        
        // Send the SMS
        $sms = $client->messages->create(
            $phoneNumber,
            [
                'from' => TWILIO_PHONE_NUMBER,
                'body' => $message
            ]
        );
        
        // Log success
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . " - SMS sent to $phoneNumber: " . $sms->sid . "\n", 
            FILE_APPEND
        );
        
        return [
            'success' => true,
            'message' => 'Verification code sent',
            'sid' => $sms->sid
        ];
    } catch (\Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . ' - SMS error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Send verification email (using PHPMailer)
function sendVerificationEmail($email, $code) {
    // Ensure PHPMailer is loaded
    $autoloadPaths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php'
    ];
    
    $loaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }
    
    if (!$loaded || !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer not found. Please run "composer require phpmailer/phpmailer".'
        ];
    }
    
    // Include mail configuration
    if (file_exists(__DIR__ . '/mail.php')) {
        require_once __DIR__ . '/mail.php';
    } else {
        return [
            'success' => false,
            'message' => 'Mail configuration not found'
        ];
    }
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Setup SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = SMTP_DEBUG ? 2 : 0;
        
        // Set sender
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addReplyTo(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        
        // Add recipient
        $mail->addAddress($email);
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = "Your Verification Code";
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .code { font-size: 24px; font-weight: bold; color: #0066cc; letter-spacing: 2px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Your Verification Code</h2>
                    <p>Please use the following code to verify your account:</p>
                    <p class='code'>$code</p>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, you can safely ignore this email.</p>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Your verification code is: $code\nThis code will expire in 10 minutes.";
        
        // Send the email
        $mail->send();
        
        // Log success
        file_put_contents(__DIR__ . '/../logs/mail-log.txt', 
            date('Y-m-d H:i:s') . " - Verification email sent to $email\n", 
            FILE_APPEND
        );
        
        return [
            'success' => true,
            'message' => 'Verification code sent to your email'
        ];
    } catch (\Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/mail-log.txt', 
            date('Y-m-d H:i:s') . ' - Email error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Calculate call cost based on duration and Twilio rate
function calculateCallCost($twilioRate, $duration, $multiplier = 200) {
    // Twilio charges per minute, rounded up to the nearest minute
    $minutes = ceil($duration / 60);
    
    // Calculate base cost
    $baseCost = $twilioRate * $minutes;
    
    // Apply markup multiplier (default 200% markup)
    $finalCost = $baseCost * ($multiplier / 100);
    
    // Round to 2 decimal places
    return round($finalCost, 2);
}

// Get call details from Twilio API
function getTwilioCallDetails($callSid) {
    try {
        // Get Twilio client
        $client = getTwilioClient();
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Failed to initialize Twilio client'
            ];
        }
        
        // Fetch call details
        $call = $client->calls($callSid)->fetch();
        
        return [
            'success' => true,
            'data' => [
                'sid' => $call->sid,
                'status' => $call->status,
                'direction' => $call->direction,
                'from' => $call->from,
                'to' => $call->to,
                'startTime' => $call->startTime ? $call->startTime->format('Y-m-d H:i:s') : null,
                'endTime' => $call->endTime ? $call->endTime->format('Y-m-d H:i:s') : null,
                'duration' => (int)$call->duration,
                'price' => $call->price,
                'priceUnit' => $call->priceUnit
            ]
        ];
    } catch (\Exception $e) {
        // Log error
        file_put_contents(__DIR__ . '/../logs/twilio-log.txt', 
            date('Y-m-d H:i:s') . ' - Get call details error: ' . $e->getMessage() . "\n", 
            FILE_APPEND
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
} 