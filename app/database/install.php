<?php
require_once __DIR__ . '/../../app/config/database.php';

try {
    $db = getDBConnection();
    
    // Read and execute schema.sql
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $db->exec($schema);
    
    echo "Database schema installed successfully!\n";
} catch (Exception $e) {
    echo "Error installing database schema: " . $e->getMessage() . "\n";
    exit(1);
} 