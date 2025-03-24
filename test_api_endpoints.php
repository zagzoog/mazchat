<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

require_once 'db_config.php';

function makeRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    $url = "http://localhost/chat/api/" . $endpoint;
    
    echo "Making request to: " . $url . "\n";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            $jsonData = json_encode($data);
            echo "POST data: " . $jsonData . "\n";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        echo "cURL Info: " . print_r(curl_getinfo($ch), true) . "\n";
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "Testing API Endpoints\n";
echo "-----------------\n\n";

// Test database connection first
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n\n";
}

// 1. First login to get session token
echo "1. Testing login...\n";
$loginResult = makeRequest('auth.php', 'POST', [
    'username' => 'testuser',
    'password' => 'testpass'
]);
echo "Login Response Code: " . $loginResult['code'] . "\n";
echo "Response: " . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "\n\n";

// Store the authentication token
$authToken = null;
if ($loginResult['code'] === 200 && isset($loginResult['response']['user'])) {
    $authToken = $loginResult['response']['user']['api_token'];
    
    // 2. Test dashboard endpoint
    echo "2. Testing dashboard endpoint...\n";
    $dashboardResult = makeRequest('dashboard.php', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Dashboard Response Code: " . $dashboardResult['code'] . "\n";
    echo "Response: " . json_encode($dashboardResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Test conversations listing
    echo "3. Testing conversations listing...\n";
    $conversationsResult = makeRequest('conversations.php', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Conversations List Response Code: " . $conversationsResult['code'] . "\n";
    echo "Response: " . json_encode($conversationsResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 4. Test conversation creation
    echo "4. Testing conversation creation...\n";
    $createConversationResult = makeRequest('conversations.php', 'POST', [
        'title' => 'Test Conversation ' . date('Y-m-d H:i:s')
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Create Conversation Response Code: " . $createConversationResult['code'] . "\n";
    echo "Response: " . json_encode($createConversationResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    if ($createConversationResult['code'] === 200 && isset($createConversationResult['response']['id'])) {
        $conversationId = $createConversationResult['response']['id'];
        
        // 5. Test sending a message
        echo "5. Testing message sending...\n";
        $sendMessageResult = makeRequest('messages.php', 'POST', [
            'conversation_id' => $conversationId,
            'content' => 'Hello, this is a test message'
        ], [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Send Message Response Code: " . $sendMessageResult['code'] . "\n";
        echo "Response: " . json_encode($sendMessageResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 6. Test getting messages
        echo "6. Testing message retrieval...\n";
        $getMessagesResult = makeRequest("messages.php?conversation_id={$conversationId}", 'GET', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Get Messages Response Code: " . $getMessagesResult['code'] . "\n";
        echo "Response: " . json_encode($getMessagesResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 7. Test chat completion
        echo "7. Testing chat completion...\n";
        $chatCompletionResult = makeRequest('chat/completion.php', 'POST', [
            'conversation_id' => $conversationId,
            'message' => 'What is the weather like today?'
        ], [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Chat Completion Response Code: " . $chatCompletionResult['code'] . "\n";
        echo "Response: " . json_encode($chatCompletionResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // 8. Test developer platform endpoints
    echo "8. Testing developer platform endpoints...\n";
    
    // 8.1 Create API key
    echo "8.1 Testing API key creation...\n";
    $createKeyResult = makeRequest('developer/keys.php', 'POST', [
        'name' => 'Test API Key',
        'description' => 'Created during automated testing'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Create API Key Response Code: " . $createKeyResult['code'] . "\n";
    echo "Response: " . json_encode($createKeyResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    if ($createKeyResult['code'] === 200 && isset($createKeyResult['response']['key'])) {
        $apiKey = $createKeyResult['response']['key'];
        $keyId = $createKeyResult['response']['id'];
        
        // 8.2 List API keys
        echo "8.2 Testing API keys listing...\n";
        $listKeysResult = makeRequest('developer/keys.php', 'GET', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "List API Keys Response Code: " . $listKeysResult['code'] . "\n";
        echo "Response: " . json_encode($listKeysResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 8.3 Test API access with key
        echo "8.3 Testing API access with key...\n";
        $testKeyResult = makeRequest('conversations.php', 'GET', null, [
            'X-API-Key: ' . $apiKey
        ]);
        echo "API Key Access Response Code: " . $testKeyResult['code'] . "\n";
        echo "Response: " . json_encode($testKeyResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 8.4 Revoke API key
        echo "8.4 Testing API key revocation...\n";
        $revokeKeyResult = makeRequest("developer/keys/{$keyId}", 'DELETE', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Revoke API Key Response Code: " . $revokeKeyResult['code'] . "\n";
        echo "Response: " . json_encode($revokeKeyResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    }

    // After testing other endpoints, add:
    echo "\n8. Testing marketplace endpoints...\n";

    // 8.1 List marketplace plugins
    echo "8.1 Testing marketplace listing...\n";
    $marketplaceResult = makeRequest('marketplace/plugins.php', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Marketplace List Response Code: " . $marketplaceResult['code'] . "\n";
    echo "Response: " . json_encode($marketplaceResult['response'], JSON_PRETTY_PRINT) . "\n\n";

    // 8.2 Get specific plugin
    echo "8.2 Testing get plugin details...\n";
    $pluginResult = makeRequest('marketplace/plugins.php?id=1', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Get Plugin Response Code: " . $pluginResult['code'] . "\n";
    echo "Response: " . json_encode($pluginResult['response'], JSON_PRETTY_PRINT) . "\n\n";

    // 8.3 Install plugin
    echo "8.3 Testing plugin installation...\n";
    $installResult = makeRequest('marketplace/plugins.php', 'POST', [
        'action' => 'install',
        'plugin_id' => 1
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Install Plugin Response Code: " . $installResult['code'] . "\n";
    echo "Response: " . json_encode($installResult['response'], JSON_PRETTY_PRINT) . "\n\n";

    // 8.4 Toggle plugin
    echo "8.4 Testing plugin toggle...\n";
    $toggleResult = makeRequest('marketplace/plugins.php', 'POST', [
        'action' => 'toggle',
        'plugin_id' => 1
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Toggle Plugin Response Code: " . $toggleResult['code'] . "\n";
    echo "Response: " . json_encode($toggleResult['response'], JSON_PRETTY_PRINT) . "\n\n";

    // 8.5 Uninstall plugin
    echo "8.5 Testing plugin uninstallation...\n";
    $uninstallResult = makeRequest('marketplace/plugins.php', 'POST', [
        'action' => 'uninstall',
        'plugin_id' => 1
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Uninstall Plugin Response Code: " . $uninstallResult['code'] . "\n";
    echo "Response: " . json_encode($uninstallResult['response'], JSON_PRETTY_PRINT) . "\n\n";
} 