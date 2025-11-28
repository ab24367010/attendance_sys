<?php
/**
 * Application Configuration
 * Loads environment variables from .env file
 */

// Prevent multiple loading
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

require_once __DIR__ . '/env.php';

// Load .env file
try {
    Env::load(__DIR__ . '/..');
} catch (Exception $e) {
    die("Failed to load environment configuration: " . $e->getMessage());
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', Env::get('DB_HOST', 'localhost'));
if (!defined('DB_USER')) define('DB_USER', Env::get('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', Env::get('DB_PASS', ''));
if (!defined('DB_NAME')) define('DB_NAME', Env::get('DB_NAME', 'attendance_sys'));

// Application Configuration
if (!defined('APP_NAME')) define('APP_NAME', Env::get('APP_NAME', 'AttendFT'));
if (!defined('APP_ENV')) define('APP_ENV', Env::get('APP_ENV', 'development'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', Env::get('APP_DEBUG', true));
if (!defined('APP_URL')) define('APP_URL', Env::get('APP_URL', 'http://localhost'));

// Session Configuration
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', Env::get('SESSION_LIFETIME', 7200));
if (!defined('SESSION_SECURE')) define('SESSION_SECURE', Env::get('SESSION_SECURE', false));
if (!defined('SESSION_HTTPONLY')) define('SESSION_HTTPONLY', Env::get('SESSION_HTTPONLY', true));

// Security
if (!defined('SECRET_KEY')) define('SECRET_KEY', Env::get('SECRET_KEY', 'change_this_secret_key'));

// Timezone
date_default_timezone_set(Env::get('TIMEZONE', 'Asia/Ulaanbaatar'));
?>