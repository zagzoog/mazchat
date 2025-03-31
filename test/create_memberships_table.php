<?php
require_once __DIR__ . '/../db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Memberships Table</h2>";

try {
    // Get database connection
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";

    // Create memberships table
    $sql = "CREATE TABLE IF NOT EXISTS memberships (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        type ENUM('free', 'silver', 'gold') NOT NULL DEFAULT 'free',
        start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        end_date TIMESTAMP NULL,
        auto_renew BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "<p style='color: green;'>✓ Memberships table created successfully!</p>";

    // Insert a test record
    $testSql = "INSERT INTO memberships (id, user_id, type, start_date, end_date, auto_renew) 
                VALUES (UUID(), (SELECT id FROM users LIMIT 1), 'gold', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), TRUE)";
    
    $db->exec($testSql);
    echo "<p style='color: green;'>✓ Test record inserted successfully!</p>";

    // Show all records
    echo "<h3>All Records:</h3>";
    $allRecords = $db->query("SELECT * FROM memberships ORDER BY created_at")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($allRecords);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} 