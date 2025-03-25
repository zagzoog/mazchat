<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

function makeRequest($ch, $url, $method = 'GET', $data = null) {
    echo "Making $method request to: $url\n";
    if ($data) {
        echo "Request data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, ''); // Enable cookie handling
    curl_setopt($ch, CURLOPT_COOKIEJAR, '');  // Enable cookie handling
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "cURL Error: " . curl_error($ch) . "\n";
    } else {
        echo "Response code: $httpCode\n";
        echo "Response body: $response\n";
    }
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "Testing Messages API\n";
echo "-------------------\n\n";

// Initialize cURL
$ch = curl_init();

// 1. First login to get token
echo "1. Testing login...\n";
$loginData = makeRequest($ch, 'http://localhost/chat/api/auth.php', 'POST', [
    'username' => 'testuser',
    'password' => 'testpass'
]);
echo "Login Response Code: " . $loginData['code'] . "\n";
echo "Response: " . json_encode($loginData['response'], JSON_PRETTY_PRINT) . "\n\n";

if ($loginData['code'] === 200) {
    // 2. Create a conversation
    echo "2. Creating a conversation...\n";
    $conversationData = makeRequest($ch, 'http://localhost/chat/api/conversations.php', 'POST', [
        'title' => 'Test Conversation',
        'plugin_id' => 9 // N8nWebhookHandler plugin ID
    ]);
    echo "Create Conversation Response Code: " . $conversationData['code'] . "\n";
    echo "Response: " . json_encode($conversationData['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    if ($conversationData['code'] === 200 && isset($conversationData['response']['data']['id'])) {
        $conversationId = $conversationData['response']['data']['id'];
        
        // 3. Send a message
        echo "3. Sending a message...\n";
        $messageData = makeRequest($ch, 'http://localhost/chat/api/messages.php', 'POST', [
            'conversation_id' => $conversationId,
            'content' => 'Hello, this is a test message'
        ]);
        echo "Send Message Response Code: " . $messageData['code'] . "\n";
        echo "Response: " . json_encode($messageData['response'], JSON_PRETTY_PRINT) . "\n\n";
    }
} else {
    echo "Login failed. Cannot proceed with other tests.\n";
}

// Close cURL session
curl_close($ch); 