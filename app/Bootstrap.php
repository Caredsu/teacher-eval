<?php
/**
 * Application Bootstrap
 * Initializes the application and loads dependencies
 */

// Set timezone to Manila, Philippines
date_default_timezone_set('Asia/Manila');

// Load constants
require_once __DIR__ . '/Constants.php';

// Load helpers
require_once __DIR__ . '/Helpers.php';

// Load database configuration
require_once CONFIG_PATH . '/database.php';

// PSR-4 Autoloader for app namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize session
init_session();

// Regenerate session ID periodically
if (!isset($_SESSION['last_regenerated']) || (time() - $_SESSION['last_regenerated']) > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}

// Set error handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Global database connection and collections (set in database.php)
global $db, $collections;
