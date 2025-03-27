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
    
    // Make sure dir_name doesn't include the domain
    if (strpos($dir_name, '.com') !== false) {
        $dir_name = '';  // or adjust as needed
    }
    
    return $dir_name;
}

// Helper function to get the full URL path
function getFullUrlPath($path = '') {
    global $config, $current_env;
    
    // Get clean domain
    $domain = rtrim($config[$current_env]['domain_name'], '/');
    
    // Clean the path and remove public_html if present
    $path = trim($path, '/');
    $path = preg_replace('#^public_html/#', '', $path);
    
    return $domain . '/' . $path;
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

// Helper function to get API URL
function getApiUrl($endpoint = '') {
    global $config, $current_env;
    
    // Get clean domain
    $domain = rtrim($config[$current_env]['domain_name'], '/');
    
    // Clean the endpoint
    $endpoint = trim($endpoint, '/');
    
    // Build clean URL
    return $domain . '/api/' . $endpoint;
}

// Export the base path for use in other files
$base_url_path = getBaseUrlPath();

// Export the current environment configuration
$current_config = $config[$current_env]; 