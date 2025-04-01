<?php
// Get the current domain
$current_domain = $_SERVER['HTTP_HOST'];

// Set database credentials based on domain if not already defined
if (!defined('DB_NAME')) {
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
} 