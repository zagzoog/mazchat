<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check user plugin preferences
    $stmt = $db->query("SELECT * FROM user_plugin_preferences");
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "User plugin preferences:\n";
    foreach ($preferences as $pref) {
        echo "User ID: " . $pref['user_id'] . "\n";
        echo "Plugin ID: " . $pref['plugin_id'] . "\n";
        echo "Created At: " . $pref['created_at'] . "\n";
        echo "-------------------\n";
    }
    
    if (empty($preferences)) {
        echo "No user plugin preferences found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 