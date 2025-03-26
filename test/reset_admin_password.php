<?php
require_once __DIR__ . '/../db_config.php';

echo "Resetting admin password...\n";

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Generate new password hash
    $password = 'adminpass';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result = $stmt->execute([$hashedPassword]);
    
    if ($result) {
        echo "✅ Admin password reset successful\n";
        echo "New password: adminpass\n";
        
        // Verify the update
        $stmt = $conn->prepare("SELECT username, role FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "Admin user details:\n";
            echo "- Username: " . $user['username'] . "\n";
            echo "- Role: " . $user['role'] . "\n";
        }
    } else {
        echo "❌ Failed to reset admin password\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 