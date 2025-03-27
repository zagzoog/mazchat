<?php
require_once __DIR__ . '/app/utils/DatabasePool.php';

// Get the current domain
$current_domain = $_SERVER['HTTP_HOST'];

// Set database credentials based on domain
if ($current_domain === 'localhost' || $current_domain === 'localhost:8080') {
    define('DB_NAME', 'mychat');
    define('DB_USER', 'mychat');
    define('DB_PASS', 'moha1212');
    define('DB_HOST', 'localhost');
} elseif ($current_domain === 'n9ib.com') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'n9ib_mychat');
    define('DB_USER', 'n9ib_mychat');
    define('DB_PASS', 'maz1212ZAM');
} else {
    // Default configuration or error handling
    die('Domain not configured for database access');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/app/logs/error.log');

function getDBConnection() {
    static $pool = null;
    
    if ($pool === null) {
        $pool = DatabasePool::getInstance();
    }
    
    return $pool->getConnection();
}

// Register shutdown function to clean up connections
register_shutdown_function(function() {
    if (isset($pool)) {
        $pool->cleanup();
    }
}); 