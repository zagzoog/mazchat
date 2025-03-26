<?php
// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test environment
define('TEST_ENV', true);

// Include necessary files
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Conversation.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/Plugin.php';
require_once __DIR__ . '/../app/models/UsageStats.php';

// Create test database connection
function getTestDBConnection() {
    static $db = null;
    if ($db === null) {
        $db = getDBConnection();
        // Use a separate test database
        $db->exec("USE mychat_test");
    }
    return $db;
}

// Helper function to create test data
function createTestUser($username = 'testuser', $email = 'test@example.com', $password = 'testpass123') {
    $db = getTestDBConnection();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (id, username, email, password, role) VALUES (UUID(), ?, ?, ?, 'user')");
    $stmt->execute([$username, $email, $hashed_password]);
    
    return $db->lastInsertId();
}

// Helper function to clean up test data
function cleanupTestData() {
    $db = getTestDBConnection();
    $tables = [
        'messages', 'conversations', 'memberships', 'usage_stats',
        'plugin_settings', 'user_plugin_preferences', 'plugin_reviews',
        'marketplace_items', 'plugin_downloads', 'plugins', 'users'
    ];
    
    foreach ($tables as $table) {
        $db->exec("DELETE FROM $table");
    }
}

// Helper function to create test conversation
function createTestConversation($user_id, $title = 'Test Conversation') {
    $db = getTestDBConnection();
    
    $stmt = $db->prepare("INSERT INTO conversations (id, user_id, title, created_at) VALUES (UUID(), ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$user_id, $title]);
    
    return $db->lastInsertId();
}

// Helper function to create test message
function createTestMessage($conversation_id, $content = 'Test message', $role = 'user') {
    $db = getTestDBConnection();
    
    $stmt = $db->prepare("INSERT INTO messages (id, conversation_id, content, role, created_at) VALUES (UUID(), ?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$conversation_id, $content, $role]);
    
    return $db->lastInsertId();
} 