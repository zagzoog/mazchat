<?php
require_once 'db_config.php';
require_once 'app/plugins/PluginManager.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

echo "Starting plugin test...\n";

try {
    // Initialize plugin manager
    $pluginManager = PluginManager::getInstance();
    echo "Plugin manager initialized\n";
    
    // Get the N8nWebhookHandler plugin
    $plugin = $pluginManager->getPlugin('N8nWebhookHandler');
    echo "Plugin retrieved: " . ($plugin ? "yes" : "no") . "\n";
    
    if ($plugin) {
        echo "Plugin class: " . get_class($plugin) . "\n";
        
        // Get plugin hooks
        $hooks = $plugin->getHooks();
        echo "Plugin hooks:\n";
        print_r($hooks);
        
        // Create a test message
        $message = [
            'conversation_id' => 86, // Using the last conversation ID from our check
            'content' => 'Test message',
            'role' => 'user'
        ];
        
        echo "\nTesting before_send_message hook...\n";
        $plugin->executeHook('before_send_message', [$message]);
        
        echo "\nTesting after_send_message hook...\n";
        $plugin->executeHook('after_send_message', [$message]);
        
    } else {
        echo "Failed to get plugin instance\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 