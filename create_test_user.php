<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Delete existing test user if exists
    $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute(['testuser']);
    
    // Create new test user
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password) 
        VALUES (?, ?, ?)
    ");
    $password = password_hash('testpass', PASSWORD_DEFAULT);
    $stmt->execute(['testuser', 'test@example.com', $password]);
    
    $userId = $db->lastInsertId();
    echo "Test user created successfully with ID: " . $userId . "\n";
    
    // Create free membership for the user
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 year'));
    
    $stmt = $db->prepare("
        INSERT INTO memberships (user_id, type, start_date, end_date) 
        VALUES (?, 'free', ?, ?)
    ");
    $stmt->execute([$userId, $start_date, $end_date]);
    
    echo "Free membership created for user\n";
    
    // Verify the user was created
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Test user details:\n";
    print_r($user);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 