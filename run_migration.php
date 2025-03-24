<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Drop payments table first
    echo "Dropping payments table...\n";
    $db->exec("DROP TABLE IF EXISTS payments");
    
    // Read and execute the migration SQL
    $sql = file_get_contents('app/database/migrations/005_create_all_tables.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
    echo "Full error: " . print_r($e, true) . "\n";
} 