<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Conversation.php';
require_once __DIR__ . '/../app/models/Message.php';
require_once __DIR__ . '/../app/models/Plugin.php';
require_once __DIR__ . '/../app/models/UsageStats.php';
require_once __DIR__ . '/../app/models/Membership.php';

// Test results array
$results = [];

// Test User Model
function testUserModel() {
    global $results;
    
    try {
        // Test user creation
        $user = new User();
        $username = 'testuser_' . time();
        $email = 'test' . time() . '@example.com';
        $password = 'test123';
        
        $userId = $user->create($username, $email, $password, 'user');
        
        if ($userId) {
            // Create free membership for the user
            $membership = new Membership();
            $membership->createFreeMembership($userId);
            
            $results[] = "✓ User creation test passed";
        } else {
            $results[] = "✗ User creation test failed";
        }
        
        // Test user retrieval
        $retrievedUser = $user->findById($userId);
        if ($retrievedUser && $retrievedUser['username'] === $username) {
            $results[] = "✓ User retrieval test passed";
        } else {
            $results[] = "✗ User retrieval test failed";
        }
        
        // Test user update
        $updateResult = $user->updateProfile($userId, $username, $email);
        if ($updateResult) {
            $results[] = "✓ User update test passed";
        } else {
            $results[] = "✗ User update test failed";
        }
        
        // Test password update
        $passwordUpdateResult = $user->updatePassword($userId, 'newpassword123');
        if ($passwordUpdateResult) {
            $results[] = "✓ Password update test passed";
        } else {
            $results[] = "✗ Password update test failed";
        }
        
        // Test password verification
        $verifyResult = $user->verifyPassword($userId, 'newpassword123');
        if ($verifyResult) {
            $results[] = "✓ Password verification test passed";
        } else {
            $results[] = "✗ Password verification test failed";
        }
        
        // Test admin check
        $isAdmin = $user->isAdmin($userId);
        if (!$isAdmin) {
            $results[] = "✓ Admin check test passed";
        } else {
            $results[] = "✗ Admin check test failed";
        }
        
        // Test last login update
        $lastLoginResult = $user->updateLastLogin($userId);
        if ($lastLoginResult) {
            $results[] = "✓ Last login update test passed";
        } else {
            $results[] = "✗ Last login update test failed";
        }
        
        // Test API key functionality
        $apiKey = $user->getApiKey($userId);
        if ($apiKey) {
            $results[] = "✓ API key retrieval test passed";
        } else {
            $results[] = "✗ API key retrieval test failed";
        }
        
        // Test finding user by API key
        $userByApiKey = $user->findByApiKey($apiKey);
        if ($userByApiKey && $userByApiKey['id'] === $userId) {
            $results[] = "✓ Find user by API key test passed";
        } else {
            $results[] = "✗ Find user by API key test failed";
        }
        
        // Test API key regeneration
        $newApiKey = $user->regenerateApiKey($userId);
        if ($newApiKey && $newApiKey !== $apiKey) {
            $results[] = "✓ API key regeneration test passed";
        } else {
            $results[] = "✗ API key regeneration test failed";
        }
        
    } catch (Exception $e) {
        $results[] = "✗ User model test failed: " . $e->getMessage();
    }
}

// Test Conversation Model
function testConversationModel() {
    global $results;
    
    try {
        // Create a test user first
        $user = new User();
        $username = 'conv_test_' . time();
        $email = 'conv' . time() . '@example.com';
        $password = 'test123';
        
        $userId = $user->create($username, $email, $password, 'user');
        
        // Create free membership for the user
        $membership = new Membership();
        $membership->createFreeMembership($userId);
        
        // Create a test plugin first
        $plugin = new PluginModel();
        $pluginId = $plugin->install('TestPlugin');
        
        // Activate the plugin
        $plugin->activate($pluginId);
        
        // Test conversation creation
        $conversation = new Conversation();
        $convId = $conversation->create($userId, $pluginId);
        
        if ($convId) {
            $results[] = "✓ Conversation creation test passed";
        } else {
            $results[] = "✗ Conversation creation test failed";
        }
        
        // Test conversation retrieval
        $retrievedConv = $conversation->getById($convId, $userId);
        if ($retrievedConv && $retrievedConv['title'] === 'محادثة جديدة') {
            $results[] = "✓ Conversation retrieval test passed";
        } else {
            $results[] = "✗ Conversation retrieval test failed";
        }
        
        // Clean up
        $conversation->delete($convId, $userId);
        $plugin->delete($pluginId);
        $user->delete($userId);
        
        $results[] = "✓ Conversation cleanup test passed";
        
    } catch (Exception $e) {
        $results[] = "✗ Conversation model test failed: " . $e->getMessage();
    }
}

// Test Message Model
function testMessageModel() {
    global $results;
    
    try {
        // Create test user and conversation
        $user = new User();
        $username = 'msg_test_' . time();
        $email = 'msg' . time() . '@example.com';
        $password = 'test123';
        
        $userId = $user->create($username, $email, $password, 'user');
        
        // Create free membership for the user
        $membership = new Membership();
        $membership->createFreeMembership($userId);
        
        // Create a test plugin
        $plugin = new PluginModel();
        $pluginId = $plugin->install('TestPlugin');
        
        // Activate the plugin
        $plugin->activate($pluginId);
        
        $conversation = new Conversation();
        $convId = $conversation->create($userId, $pluginId);
        
        // Test message creation
        $message = new Message();
        $msgId = $message->create([
            'conversation_id' => $convId,
            'content' => 'Test message content',
            'role' => 'user'
        ]);
        
        if ($msgId) {
            $results[] = "✓ Message creation test passed";
        } else {
            $results[] = "✗ Message creation test failed";
        }
        
        // Test message retrieval
        $retrievedMsg = $message->getById($msgId);
        if ($retrievedMsg && $retrievedMsg['content'] === 'Test message content') {
            $results[] = "✓ Message retrieval test passed";
        } else {
            $results[] = "✗ Message retrieval test failed";
        }
        
        // Clean up
        $message->delete($msgId);
        $conversation->delete($convId, $userId);
        $plugin->delete($pluginId);
        $user->delete($userId);
        
        $results[] = "✓ Message cleanup test passed";
        
    } catch (Exception $e) {
        $results[] = "✗ Message model test failed: " . $e->getMessage();
    }
}

// Run all tests
echo "Starting tests...\n\n";

testUserModel();
testConversationModel();
testMessageModel();

// Display results
echo "\nTest Results:\n";
echo "=============\n";
foreach ($results as $result) {
    echo $result . "\n";
}

echo "\nTotal tests: " . count($results) . "\n";
$passed = count(array_filter($results, function($r) { return strpos($r, '✓') !== false; }));
echo "Passed: " . $passed . "\n";
echo "Failed: " . (count($results) - $passed) . "\n"; 