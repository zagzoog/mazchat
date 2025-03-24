<?php
require_once 'app/models/Model.php';  // Load base Model class first
require_once 'db_config.php';
require_once 'app/models/User.php';
require_once 'app/models/Conversation.php';
require_once 'app/models/Message.php';

// Initialize cURL session
$ch = curl_init();

// Set common cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_ENCODING, ''); // Handle compressed responses automatically

// Function to make request and handle response
function makeRequest($ch, $method, $url, $data = null) {
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, null);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        return null;
    }
    
    echo str_repeat('-', 80) . "\n";
    echo "$method $url\n";
    echo "Response Code: $httpCode\n";
    
    // Try to parse and pretty print JSON response
    $jsonData = json_decode($response, true);
    if ($jsonData !== null) {
        echo "Response:\n" . json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Raw Response: " . $response . "\n";
    }
    echo str_repeat('-', 80) . "\n\n";
    
    return $httpCode === 200 ? $jsonData : null;
}

// Test login
echo "Testing login...\n";
$loginData = makeRequest($ch, 'POST', 'http://localhost/chat/api/auth.php', [
    'username' => 'testuser',
    'password' => 'testpass123'
]);

if (!$loginData) {
    die("Login failed. Cannot proceed with other tests.\n");
}

// Test dashboard endpoint
echo "Testing dashboard endpoint...\n";
$dashboardData = makeRequest($ch, 'GET', 'http://localhost/chat/api/dashboard.php');

// Test conversations endpoint
echo "Testing conversations endpoint...\n";
$conversationsData = makeRequest($ch, 'GET', 'http://localhost/chat/api/conversations.php');

// Test creating a new conversation
echo "Testing conversation creation...\n";
$newConversation = makeRequest($ch, 'POST', 'http://localhost/chat/api/conversations.php', [
    'title' => 'Test Conversation'
]);

if ($newConversation && isset($newConversation['id'])) {
    $conversationId = $newConversation['id'];
    
    // Test messages endpoint
    echo "Testing messages endpoint...\n";
    $messagesData = makeRequest($ch, 'GET', "http://localhost/chat/api/messages.php?conversation_id=$conversationId");
    
    // Test creating a new message
    echo "Testing message creation...\n";
    $newMessage = makeRequest($ch, 'POST', 'http://localhost/chat/api/messages.php', [
        'conversation_id' => $conversationId,
        'content' => 'Test message',
        'is_user' => true
    ]);
}

// Close cURL session
curl_close($ch); 