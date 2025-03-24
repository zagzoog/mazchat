<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

function makeRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    $url = "http://localhost/chat/app/api/v1/" . $endpoint;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "Testing Developer Portal API Key Management\n";
echo "----------------------------------------\n\n";

// 1. First login to get session token
echo "1. Testing login...\n";
$loginResult = makeRequest('auth.php', 'POST', [
    'username' => 'testuser',
    'password' => 'testpass123'
]);
echo "Login Response Code: " . $loginResult['code'] . "\n";
echo "Response: " . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "\n\n";

// Store the authentication token
$authToken = null;
if ($loginResult['code'] === 200 && isset($loginResult['response']['data']['token'])) {
    $authToken = $loginResult['response']['data']['token'];
    
    // 2. Generate new API key
    echo "2. Testing API key generation...\n";
    $keyGenResult = makeRequest('developer/keys.php', 'POST', [
        'name' => 'Test API Key',
        'description' => 'Key for testing purposes',
        'rate_limit' => 100
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Key Generation Response Code: " . $keyGenResult['code'] . "\n";
    echo "Response: " . json_encode($keyGenResult['response'], JSON_PRETTY_PRINT) . "\n\n";

    // Store the API key if generated
    $apiKey = null;
    $keyId = null;
    if ($keyGenResult['code'] === 200 && isset($keyGenResult['response']['data']['api_key'])) {
        $apiKey = $keyGenResult['response']['data']['api_key'];
        $keyId = $keyGenResult['response']['data']['id'];
        
        // 3. Test API access with generated key
        echo "3. Testing API access with generated key...\n";
        $testResult = makeRequest('conversations.php', 'GET', null, [
            'X-API-Key: ' . $apiKey
        ]);
        echo "API Access Response Code: " . $testResult['code'] . "\n";
        echo "Response: " . json_encode($testResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 4. Get usage statistics
        echo "4. Testing API key usage statistics...\n";
        $usageResult = makeRequest("developer/keys/{$keyId}/usage", 'GET', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Usage Stats Response Code: " . $usageResult['code'] . "\n";
        echo "Response: " . json_encode($usageResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 5. Toggle key status
        echo "5. Testing API key toggle...\n";
        $toggleResult = makeRequest("developer/keys/{$keyId}/toggle", 'POST', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Toggle Response Code: " . $toggleResult['code'] . "\n";
        echo "Response: " . json_encode($toggleResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 6. Test API access with toggled key
        echo "6. Testing API access with toggled key...\n";
        $testToggleResult = makeRequest('conversations.php', 'GET', null, [
            'X-API-Key: ' . $apiKey
        ]);
        echo "Toggled Key Access Response Code: " . $testToggleResult['code'] . "\n";
        echo "Response: " . json_encode($testToggleResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 7. Revoke key
        echo "7. Testing API key revocation...\n";
        $revokeResult = makeRequest("developer/keys/{$keyId}", 'DELETE', null, [
            'Authorization: Bearer ' . $authToken
        ]);
        echo "Revocation Response Code: " . $revokeResult['code'] . "\n";
        echo "Response: " . json_encode($revokeResult['response'], JSON_PRETTY_PRINT) . "\n\n";
        
        // 8. Test API access with revoked key
        echo "8. Testing API access with revoked key...\n";
        $testRevokedResult = makeRequest('conversations.php', 'GET', null, [
            'X-API-Key: ' . $apiKey
        ]);
        echo "Revoked Key Access Response Code: " . $testRevokedResult['code'] . "\n";
        echo "Response: " . json_encode($testRevokedResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    }
} 