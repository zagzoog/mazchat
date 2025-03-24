<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'chat_db';
    $username = 'your_username';
    $password = 'your_password';
    $charset = 'utf8mb4';

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
} 