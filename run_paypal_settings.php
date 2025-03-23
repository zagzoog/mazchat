<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Read the SQL file
    $sql = file_get_contents('migrations/insert_paypal_settings.sql');
    
    // Execute the SQL
    $db->exec($sql);
    
    echo "PayPal settings have been updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 