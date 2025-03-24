<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

require_once 'db_config.php';
require_once 'app/models/Model.php';
require_once 'app/models/User.php';

try {
    echo "Testing database connection...\n";
    $db = getDBConnection();
    echo "Database connection successful!\n";
    
    echo "\nTesting User model...\n";
    $user = new User();
    echo "User model instantiated successfully!\n";
    
    // Create a test user
    $username = 'testuser';
    $email = 'test@example.com';
    $password = 'testpass123';
    
    echo "\nAttempting to create test user...\n";
    try {
        $userId = $user->create($username, $email, $password);
        echo "Test user created successfully with ID: $userId\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') { // Duplicate entry
            echo "Test user already exists, proceeding with login test...\n";
        } else {
            throw $e;
        }
    }
    
    echo "\nTesting user lookup...\n";
    $userData = $user->findByUsername($username);
    if ($userData) {
        echo "User found:\n";
        print_r($userData);
    } else {
        echo "User not found!\n";
    }
    
    echo "\nTesting password verification...\n";
    if ($userData && password_verify($password, $userData['password'])) {
        echo "Password verification successful!\n";
    } else {
        echo "Password verification failed!\n";
    }
    
    echo "\nTesting auth endpoint directly...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/chat/app/api/v1/auth.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";

    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    }

    curl_close($ch);
    
    // Test the response compression
    echo "\nTesting response compression...\n";
    $ch = curl_init('http://localhost/chat/api/auth.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept-Encoding: gzip, deflate'
    ]);
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "Compression Info:\n";
    echo "Content-Encoding: " . ($info['content_encoding'] ?? 'none') . "\n";
    echo "Size Downloaded: " . $info['size_download'] . " bytes\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 