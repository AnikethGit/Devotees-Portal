<?php
// config.php - Database and Application Configuration
define('BASE_URL', 'http://sadgurubharadwaja.org/devotees-portal/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u701659873_task');
define('DB_PASS', '#5PzJjRE');
define('DB_NAME', 'u701659873_devoteesportal');

// Application Configuration
define('APP_NAME', 'Sadguru Bharadwaja - Devotees Portal');
define('APP_URL', 'https://yourdomain.com/devotees-portal');
define('UPLOAD_DIR', __DIR__ . '/uploads/downloads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['pdf', 'xls', 'xlsx', 'doc', 'docx']);

// Session Configuration
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes in seconds
define('SESSION_NAME', 'devotees_portal_session');

// Security Configuration
define('HASH_ALGORITHM', 'bcrypt');
define('CSRF_TOKEN_LENGTH', 32);

// Email Configuration (Update with your email service)
define('SMTP_HOST', 'smtp.hostinger.com'); // Change to your SMTP
define('SMTP_PORT', 465);
define('SMTP_USER', 'noreply@sadgurubharadwaja.org');
define('SMTP_PASS', 'your_email_password');
define('FROM_EMAIL', 'noreply@sadgurubharadwaja.org');
define('FROM_NAME', 'Sadguru Bharadwaja Portal');

// SMS Configuration (You can use services like Twilio, AWS SNS, etc.)
// For now, we'll use a placeholder - implement your SMS service
define('SMS_PROVIDER', 'twilio'); // or 'custom'
define('SMS_API_KEY', 'your_sms_api_key');
define('SMS_FROM_NUMBER', '+91XXXXXXXXXX');

// Contact Details (Displayed on pages)
define('CONTACT_PHONE', '+91-XXXXXXXXXX');
define('CONTACT_EMAIL', 'info@sadgurubharadwaja.org');
define('CONTACT_ADDRESS', 'Your Address Here');

// Error Reporting (Set to 0 in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Create necessary directories
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>