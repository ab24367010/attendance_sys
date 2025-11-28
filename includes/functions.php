<?php
/**
 * Common Functions
 * Helper functions used throughout the application
 */

// Load configuration and dependencies
require_once __DIR__ . '/../config/db.php';

// Load Auth class
if (!class_exists('Auth')) {
    require_once __DIR__ . '/../auth/Auth.php';
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display flash message
 */
function flashMessage($key, $message = null, $type = 'info') {
    Auth::startSession();

    if ($message === null) {
        // Get message
        if (isset($_SESSION['flash_' . $key])) {
            $flash = $_SESSION['flash_' . $key];
            unset($_SESSION['flash_' . $key]);
            return $flash;
        }
        return null;
    } else {
        // Set message
        $_SESSION['flash_' . $key] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (!$date) return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Get base URL
 */
function baseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];

    // Get the document root path
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptName);

    // Remove subdirectories (teacher, student, etc.) to get project root
    $baseDir = preg_replace('#/(teacher|student|admin)(/|$)#', '', $scriptDir);
    $baseDir = rtrim($baseDir, '/');

    return $protocol . '://' . $host . $baseDir . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path) {
    return baseUrl('assets/' . ltrim($path, '/'));
}

/**
 * Check if current page is active
 */
function isActive($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}

/**
 * Debug helper - only works when APP_DEBUG is true
 */
function dd($data) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

/**
 * Log message to file
 */
function logMessage($message, $level = 'INFO') {
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }

    $logFile = __DIR__ . '/../logs/app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Get page title
 */
function getPageTitle($default = 'AttendFT') {
    global $pageTitle;
    return isset($pageTitle) ? $pageTitle . ' - AttendFT' : $default;
}
?>
