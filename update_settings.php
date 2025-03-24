<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    $stmt = $db->prepare('UPDATE admin_settings SET setting_value = ? WHERE setting_key = ?');
    $stmt->execute(['500', 'free_question_limit']);
    echo "Successfully updated free_question_limit to 500\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 