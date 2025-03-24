<?php
require_once __DIR__ . '/app/utils/DatabasePool.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mychat');
define('DB_USER', 'mychat');
define('DB_PASS', 'moha1212');

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