<?php
require_once 'db_config.php';

function executeSQLFile($file) {
    $db = getDBConnection();
    $sql = file_get_contents($file);
    
    try {
        $db->exec($sql);
        echo "Successfully executed $file\n";
    } catch (PDOException $e) {
        echo "Error executing $file: " . $e->getMessage() . "\n";
    }
}

// Install tables in order
$sqlFiles = [
    'database.sql',                    // Core tables
    'create_messages_table.sql',       // Messages table
    'app/api/migrations/create_api_keys_table.sql'  // API keys tables
];

foreach ($sqlFiles as $file) {
    if (file_exists($file)) {
        executeSQLFile($file);
    } else {
        echo "File not found: $file\n";
    }
}

echo "\nDatabase installation complete!\n"; 