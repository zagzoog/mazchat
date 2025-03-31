<?php
require_once __DIR__ . '/../db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

try {
    // Get database connection
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";

    // Check if memberships table exists
    $tables = $db->query("SHOW TABLES LIKE 'memberships'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "<p style='color: red;'>✗ memberships table does not exist!</p>";
    } else {
        echo "<p style='color: green;'>✓ memberships table exists!</p>";

        // Check if record with ID 8 exists
        $stmt = $db->prepare("SELECT * FROM memberships WHERE id = ?");
        $stmt->execute([8]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            echo "<p style='color: green;'>✓ Record with ID 8 exists!</p>";
            echo "<h3>Record Details:</h3>";
            echo "<pre>";
            print_r($record);
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>! No record found with ID 8</p>";
        }

        // Show all records
        echo "<h3>All Records:</h3>";
        $allRecords = $db->query("SELECT * FROM memberships ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($allRecords);
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} 