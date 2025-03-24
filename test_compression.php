<?php
require_once 'app/utils/ResponseCompressor.php';

// Function to test compression
function testCompression($url, $method = 'GET', $data = null, $cookies = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Encoding: gzip, deflate',
        'User-Agent: Compression-Test/1.0'
    ]);
    
    // Add cookie handling
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    
    if ($method === 'POST' && $data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Content-Type: application/json'],
            ['Accept-Encoding: gzip, deflate'],
            ['User-Agent: Compression-Test/1.0']
        ));
    }
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'error' => $error,
            'compressed' => false,
            'content_encoding' => 'none',
            'response_size' => 0,
            'response' => null
        ];
    }
    
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'compressed' => isset($info['size_download']) && $info['size_download'] > 0,
        'content_encoding' => isset($info['content_encoding']) ? $info['content_encoding'] : 'none',
        'response_size' => $info['size_download'],
        'response' => $response,
        'http_code' => $info['http_code']
    ];
}

// First, let's login to get a session
$loginUrl = 'http://localhost/chat/api/auth.php';
$loginData = [
    'username' => 'testuser',
    'password' => 'testpass123'
];

echo "Attempting login...\n";
$loginResult = testCompression($loginUrl, 'POST', $loginData);
echo "Login response code: " . $loginResult['http_code'] . "\n";
echo "Login response: " . $loginResult['response'] . "\n\n";

if ($loginResult['http_code'] !== 200) {
    die("Failed to login. Please ensure test user exists and credentials are correct.\n");
}

// Extract session cookie from login response
$sessionCookie = '';
if (preg_match('/PHPSESSID=([^;]+)/', $loginResult['response'], $matches)) {
    $sessionCookie = 'PHPSESSID=' . $matches[1];
}

// Test URLs
$baseUrl = 'http://localhost/chat/api';
$endpoints = [
    'conversations' => $baseUrl . '/conversations.php',
    'messages' => $baseUrl . '/messages.php',
    'usage_stats' => $baseUrl . '/usage_stats.php'
];

echo "Testing Response Compression\n";
echo "===========================\n\n";

foreach ($endpoints as $name => $url) {
    echo "Testing $name endpoint:\n";
    echo "------------------------\n";
    
    // Test GET request
    $result = testCompression($url, 'GET', null, $sessionCookie);
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        echo "GET Request:\n";
        echo "Compressed: " . ($result['compressed'] ? 'Yes' : 'No') . "\n";
        echo "Content-Encoding: " . $result['content_encoding'] . "\n";
        echo "Response Size: " . $result['response_size'] . " bytes\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        echo "Response: " . substr($result['response'], 0, 100) . "...\n\n";
    }
    
    // Test POST request for endpoints that support it
    if ($name === 'conversations' || $name === 'messages') {
        $postData = $name === 'conversations' 
            ? ['title' => 'Test Conversation']
            : ['conversation_id' => 1, 'content' => 'Test Message'];
            
        $result = testCompression($url, 'POST', $postData, $sessionCookie);
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        } else {
            echo "POST Request:\n";
            echo "Compressed: " . ($result['compressed'] ? 'Yes' : 'No') . "\n";
            echo "Content-Encoding: " . $result['content_encoding'] . "\n";
            echo "Response Size: " . $result['response_size'] . " bytes\n";
            echo "HTTP Code: " . $result['http_code'] . "\n";
            echo "Response: " . substr($result['response'], 0, 100) . "...\n\n";
        }
    }
    
    echo "------------------------\n\n";
}

// Test ResponseCompressor class directly
echo "Testing ResponseCompressor class:\n";
echo "--------------------------------\n";
$compressor = ResponseCompressor::getInstance();
echo "Compression Enabled: " . ($compressor->isCompressionEnabled() ? 'Yes' : 'No') . "\n";
echo "Zlib Extension Loaded: " . (extension_loaded('zlib') ? 'Yes' : 'No') . "\n";
echo "Zlib Output Compression: " . ini_get('zlib.output_compression') . "\n";
echo "Accept-Encoding Header: " . (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : 'Not Set') . "\n"; 