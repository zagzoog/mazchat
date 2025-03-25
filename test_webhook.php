<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/webhook_test.log');

$webhookUrl = 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f';
$data = [
    'message' => 'test message',
    'conversation_id' => 86,
    'timestamp' => date('Y-m-d H:i:s')
];

echo "Testing webhook URL: " . $webhookUrl . "\n";
echo "Sending data: " . json_encode($data) . "\n\n";

// Initialize cURL
$ch = curl_init($webhookUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// SSL Options
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

// Verbose output
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);

echo "Verbose information:\n" . $verboseLog . "\n\n";

if ($response === false) {
    echo "Error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
}

curl_close($ch); 