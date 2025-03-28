<?php
// Environment configuration
if (!defined('ENVIRONMENT')) {
    // Get the current domain
    $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
    
    // Set environment based on domain
    if ($currentDomain === 'localhost' || strpos($currentDomain, 'localhost') !== false) {
        define('ENVIRONMENT', 'test');
    } elseif ($currentDomain === 'n9ib.com' || strpos($currentDomain, 'n9ib.com') !== false) {
        define('ENVIRONMENT', 'production');
    } else {
        // Default to test environment for safety
        define('ENVIRONMENT', 'test');
    }
}