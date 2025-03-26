<?php
require_once __DIR__ . '/../db_config.php';

echo "Testing database connection...\n";

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Test the connection with a simple query
    $result = $conn->query("SELECT 1");
    
    if ($result) {
        echo "✅ Database connection successful!\n";
        echo "Connection details:\n";
        echo "- Host: " . DB_HOST . "\n";
        echo "- Database: " . DB_NAME . "\n";
        echo "- User: " . DB_USER . "\n";
    } else {
        echo "❌ Database connection failed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 