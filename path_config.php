<?php
// Get the base path from config
$config = require_once __DIR__ . '/config.php';

// Get the current environment
$current_env = defined('ENVIRONMENT') ? ENVIRONMENT : 'test';

// Ensure the environment exists in config
if (!isset($config[$current_env])) {
    die("Configuration for environment '{$current_env}' not found");
}

// Get the base path for the current environment
$base_path = $config[$current_env]['directory_path'];

// Helper function to get the base URL path
function getBaseUrlPath() {
    global $config, $current_env;
    $path = $config[$current_env]['directory_path'];
    
    // Remove any trailing slashes
    $path = rtrim($path, '/');
    
    // Extract the directory name from the full path
    $dir_name = basename($path);
    
    return $dir_name;
}

// Helper function to get the full URL path
function getFullUrlPath($path = '') {
    global $config, $current_env;
    $domain = $config[$current_env]['domain_name'];
    
    // Remove any trailing slashes from domain
    $domain = rtrim($domain, '/');
    
    // Ensure path starts with a slash
    $path = '/' . ltrim($path, '/');
    
    return $domain . $path;
}

// Helper function to get the full file path
function getFullFilePath($path = '') {
    global $config, $current_env;
    $base_path = $config[$current_env]['directory_path'];
    
    // Remove any trailing slashes
    $base_path = rtrim($base_path, '/');
    
    // Ensure path starts with a slash
    $path = '/' . ltrim($path, '/');
    
    return $base_path . $path;
}

// Export the base path for use in other files
$base_url_path = getBaseUrlPath();

// Export the current environment configuration
$current_config = $config[$current_env]; 