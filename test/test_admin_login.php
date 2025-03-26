<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/models/User.php';

echo "Testing admin login...\n";

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Test admin login with default credentials
    $username = 'admin';
    $password = 'adminpass';
    
    // Query for admin user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Admin user found in database\n";
        echo "User details:\n";
        echo "- Username: " . $user['username'] . "\n";
        echo "- Role: " . $user['role'] . "\n";
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            echo "✅ Admin password verification successful\n";
            
            // Test User model's isAdmin method
            $userModel = new User();
            if ($userModel->isAdmin($user['id'])) {
                echo "✅ User model correctly identifies admin role\n";
            } else {
                echo "❌ User model failed to identify admin role\n";
            }
        } else {
            echo "❌ Admin password verification failed\n";
        }
    } else {
        echo "❌ Admin user not found in database\n";
        echo "Please run create_admin.php first to create the admin user\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 