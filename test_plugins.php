<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';
require_once 'app/plugins/PluginManager.php';

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

echo "Testing Plugin System\n";
echo "-------------------\n\n";

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
if ($loginResult['code'] === 200 && isset($loginResult['response']['data']['token'])) {
    $authToken = $loginResult['response']['data']['token'];
    
    // 2. Get list of available plugins
    echo "2. Testing plugin listing...\n";
    $pluginsResult = makeRequest('plugins.php', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Plugins Response Code: " . $pluginsResult['code'] . "\n";
    echo "Response: " . json_encode($pluginsResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Create a test plugin
    echo "3. Creating test plugin directory...\n";
    $pluginDir = 'plugins/TestPlugin';
    if (!file_exists($pluginDir)) {
        mkdir($pluginDir, 0777, true);
    }
    
    // Create plugin file
    $pluginFile = $pluginDir . '/TestPlugin.php';
    $pluginCode = '<?php
require_once dirname(__DIR__) . "/Plugin.php";

class TestPlugin extends Plugin {
    public function __construct() {
        $this->name = "TestPlugin";
        $this->version = "1.0.0";
        $this->description = "A test plugin";
        $this->author = "Test Author";
        
        parent::__construct();
    }
    
    public function initialize() {
        $this->registerHook("before_send_message", [$this, "modifyMessage"]);
    }
    
    public function activate() {
        $db = getDBConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS test_plugin_data (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    public function modifyMessage($message) {
        $message["content"] = "[Test Plugin] " . $message["content"];
        return $message;
    }
}';
    
    file_put_contents($pluginFile, $pluginCode);
    echo "Test plugin created at: " . $pluginFile . "\n\n";
    
    // 4. Activate the test plugin
    echo "4. Testing plugin activation...\n";
    $activateResult = makeRequest('plugins.php', 'POST', [
        'action' => 'activate',
        'name' => 'TestPlugin'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Activation Response Code: " . $activateResult['code'] . "\n";
    echo "Response: " . json_encode($activateResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 5. Test plugin functionality
    echo "5. Testing plugin functionality...\n";
    $testResult = makeRequest('messages.php', 'POST', [
        'conversation_id' => '1',
        'content' => 'Hello, this is a test message'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Message Response Code: " . $testResult['code'] . "\n";
    echo "Response: " . json_encode($testResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 6. Deactivate the test plugin
    echo "6. Testing plugin deactivation...\n";
    $deactivateResult = makeRequest('plugins.php', 'POST', [
        'action' => 'deactivate',
        'name' => 'TestPlugin'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Deactivation Response Code: " . $deactivateResult['code'] . "\n";
    echo "Response: " . json_encode($deactivateResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 7. Clean up
    echo "7. Cleaning up...\n";
    array_map('unlink', glob($pluginDir . '/*.*'));
    rmdir($pluginDir);
    echo "Test plugin directory removed\n";
} 