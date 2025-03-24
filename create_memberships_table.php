<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Create memberships table
    $db->exec("
        CREATE TABLE IF NOT EXISTS memberships (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'free',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create a free membership for the test user
    $stmt = $db->prepare("
        INSERT INTO memberships (user_id, type, start_date, end_date)
        VALUES (?, 'free', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))
    ");
    $stmt->execute([1]); // 1 is the test user's ID
    
    echo "Memberships table created and test user membership added successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 