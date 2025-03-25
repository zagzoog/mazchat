<?php
$dbConfig = [
    'host' => 'localhost',
    'name' => 'mychat',
    'user' => 'mychat',
    'pass' => 'moha1212'
];

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Database Schema Export\n";
    $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Get create table statement
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= $createTable['Create Table'] . ";\n\n";
        
        // Get foreign keys
        $foreignKeys = $pdo->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                                  WHERE TABLE_SCHEMA = '{$dbConfig['name']}' 
                                  AND TABLE_NAME = '$table' 
                                  AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($foreignKeys as $fk) {
            $output .= "ALTER TABLE `$table` ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` 
                       FOREIGN KEY (`{$fk['COLUMN_NAME']}`) 
                       REFERENCES `{$fk['REFERENCED_TABLE_NAME']}` (`{$fk['REFERENCED_COLUMN_NAME']}`);\n";
        }
        $output .= "\n";
    }
    
    // Save to file
    file_put_contents('database/schema.sql', $output);
    echo "Schema exported successfully to database/schema.sql\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 