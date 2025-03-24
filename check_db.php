<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check users table structure
    echo "Users table structure:\n";
    $stmt = $db->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    // Check if test user exists
    echo "\nTest user:\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['testuser']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($user);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 