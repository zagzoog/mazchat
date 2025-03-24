<?php
require_once 'db_config.php';

try {
    // Connect without database selected
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS mychat");
    echo "Database dropped successfully\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE mychat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
    
    // Select database
    $pdo->exec("USE mychat");
    
    // Read and execute database.sql
    $sql = file_get_contents('database.sql');
    $pdo->exec($sql);
    echo "Database schema created successfully\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 