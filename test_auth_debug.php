<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    require_once 'db_config.php';
    $db = getDBConnection();
    echo "Database connection successful!\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test user exists
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['testuser']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "Test user found:\n";
        print_r($user);
    } else {
        echo "Test user not found!\n";
    }
} catch (Exception $e) {
    echo "Error checking test user: " . $e->getMessage() . "\n";
    exit;
}

// Test API endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/chat/app/api/v1/auth.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'testuser',
    'password' => 'testpass'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "\nHTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
}

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "\nVerbose cURL log:\n" . $verboseLog . "\n";

curl_close($ch); 