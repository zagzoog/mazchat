<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check plugins table
    $stmt = $db->query("SELECT * FROM plugins");
    $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Plugins in database:\n";
    foreach ($plugins as $plugin) {
        echo "ID: " . $plugin['id'] . "\n";
        echo "Name: " . $plugin['name'] . "\n";
        echo "Is Active: " . ($plugin['is_active'] ? 'Yes' : 'No') . "\n";
        echo "Created At: " . $plugin['created_at'] . "\n";
        echo "Updated At: " . $plugin['updated_at'] . "\n";
        echo "-------------------\n";
    }
    
    if (empty($plugins)) {
        echo "No plugins found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 