<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check if admin user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    
    if (!$stmt->fetch()) {
        // Create admin user
        $stmt = $db->prepare("
            INSERT INTO users (id, username, email, password, role, membership_type) 
            VALUES (UUID(), ?, ?, ?, 'admin', 'premium')
        ");
        
        $password = password_hash('adminpass', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@example.com', $password]);
        
        echo "Admin user created successfully!\n";
    } else {
        echo "Admin user already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 