<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mychat');  // Change this to your MySQL username
define('DB_PASS', 'moha1212');      // Change this to your MySQL password
define('DB_NAME', 'mychat');

// Create database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );
        return $conn;
    } catch(PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
} 