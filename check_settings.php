<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    $stmt = $db->query('SELECT setting_key, setting_value FROM admin_settings');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['setting_key'] . ': ' . $row['setting_value'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 