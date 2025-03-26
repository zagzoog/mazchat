<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    $db = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!<br>";
} catch(PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Test Apache
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test environment variables
echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
echo "DB_NAME: " . getenv('DB_NAME') . "<br>";
echo "DB_USER: " . getenv('DB_USER') . "<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? "Set" : "Not Set") . "<br>"; 