<?php
require_once __DIR__ . '/../db_config.php';

try {
    $db = getDBConnection();
    
    // Check if test database exists
    $result = $db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'mychat_test'");
    $dbExists = $result->fetch() !== false;
    
    if (!$dbExists) {
        // Try to create the database
        try {
            $db->exec("CREATE DATABASE mychat_test");
        } catch (PDOException $e) {
            // If we can't create the database, try to use the existing database
            echo "Warning: Could not create test database. Using existing database.\n";
            $db->exec("USE mychat");
        }
    } else {
        $db->exec("USE mychat_test");
    }
    
    // Read and execute the complete schema
    $schema = file_get_contents(__DIR__ . '/../sql/complete_schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Skip if table already exists
                if ($e->getCode() != '42S01') { // 42S01 is "Table already exists"
                    throw $e;
                }
            }
        }
    }
    
    echo "Test database setup completed successfully.\n";
} catch (Exception $e) {
    echo "Error setting up test database: " . $e->getMessage() . "\n";
    exit(1);
} 