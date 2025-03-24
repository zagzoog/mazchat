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

echo "Testing Plugin Marketplace\n";
echo "------------------------\n\n";

// 1. First login to get session token
echo "1. Testing login...\n";
$loginResult = makeRequest('auth.php', 'POST', [
    'username' => 'admin',
    'password' => 'adminpass'
]);
echo "Login Response Code: " . $loginResult['code'] . "\n";
echo "Response: " . json_encode($loginResult['response'], JSON_PRETTY_PRINT) . "\n\n";

// Store the authentication token
$authToken = null;
if ($loginResult['code'] === 200 && isset($loginResult['response']['data']['token'])) {
    $authToken = $loginResult['response']['data']['token'];
    
    // 2. Create a test plugin ZIP file
    echo "2. Creating test plugin ZIP...\n";
    $pluginDir = 'test_plugin/TestMarketplacePlugin';
    if (!file_exists($pluginDir)) {
        mkdir($pluginDir, 0777, true);
    }
    
    // Create plugin files
    $pluginCode = '<?php
require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class TestMarketplacePlugin extends Plugin {
    public function __construct() {
        $this->name = "TestMarketplacePlugin";
        $this->version = "1.0.0";
        $this->description = "A test plugin for marketplace";
        $this->author = "Test Author";
        
        parent::__construct();
    }
    
    public function initialize() {
        $this->registerHook("before_send_message", [$this, "modifyMessage"]);
        $this->registerHook("admin_settings_page", [$this, "renderSettings"]);
    }
    
    public function activate() {
        $db = getDBConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS test_marketplace_data (
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
        $message["content"] = "[Marketplace Plugin] " . $message["content"];
        return $message;
    }
    
    public function renderSettings() {
        echo "<div class=\"card\">
            <div class=\"card-header\">
                <h5 class=\"card-title mb-0\">Test Marketplace Plugin Settings</h5>
            </div>
            <div class=\"card-body\">
                <form id=\"testMarketplaceSettings\">
                    <div class=\"mb-3\">
                        <label for=\"prefix\" class=\"form-label\">Message Prefix</label>
                        <input type=\"text\" class=\"form-control\" id=\"prefix\" name=\"prefix\" value=\"[Marketplace Plugin]\">
                    </div>
                    <button type=\"submit\" class=\"btn btn-primary\">Save Settings</button>
                </form>
            </div>
        </div>";
    }
}';
    
    // Create plugin directory
    $pluginDir = 'test_plugin/TestMarketplacePlugin';
    if (!file_exists($pluginDir)) {
        mkdir($pluginDir, 0777, true);
    }
    
    // Create plugin file with the same name as the directory
    file_put_contents($pluginDir . '/TestMarketplacePlugin.php', $pluginCode);
    
    // Create config file
    $config = [
        'prefix' => '[Marketplace Plugin]'
    ];
    file_put_contents($pluginDir . '/config.php', '<?php return ' . var_export($config, true) . ';');
    
    // Create README
    file_put_contents($pluginDir . '/README.md', '# Test Marketplace Plugin\n\nA test plugin for the marketplace.');
    
    // Create ZIP file
    $zip = new ZipArchive();
    $zipFile = 'test_plugin.zip';
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Add files with the correct directory structure
        $files = [
            'TestMarketplacePlugin/TestMarketplacePlugin.php' => $pluginCode,
            'TestMarketplacePlugin/config.php' => '<?php return ' . var_export($config, true) . ';',
            'TestMarketplacePlugin/README.md' => '# Test Marketplace Plugin\n\nA test plugin for the marketplace.'
        ];
        
        foreach ($files as $target => $content) {
            $zip->addFromString($target, $content);
        }
        
        $zip->close();
        echo "Test plugin ZIP created at: " . $zipFile . "\n\n";
    } else {
        echo "Failed to create ZIP file\n\n";
    }
    
    // 3. Upload and install the plugin
    echo "3. Testing plugin upload...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/chat/app/api/v1/plugins.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $boundary = uniqid();
    $delimiter = '-------------' . $boundary;
    
    $postData = array(
        'plugin_file' => new CURLFile($zipFile, 'application/zip', 'plugin.zip')
    );
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken,
        'Content-Type: multipart/form-data'
    ]);
    
    $uploadResponse = curl_exec($ch);
    $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Upload Response Code: " . $uploadCode . "\n";
    echo "Response: " . $uploadResponse . "\n\n";
    
    // 4. Activate the plugin
    echo "4. Testing plugin activation...\n";
    $activateResult = makeRequest('plugins.php', 'POST', [
        'action' => 'activate',
        'name' => 'TestMarketplacePlugin'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Activation Response Code: " . $activateResult['code'] . "\n";
    echo "Response: " . json_encode($activateResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 5. Get plugin settings
    echo "5. Testing plugin settings...\n";
    $settingsResult = makeRequest('plugins.php?name=TestMarketplacePlugin', 'GET', null, [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Settings Response Code: " . $settingsResult['code'] . "\n";
    echo "Response: " . json_encode($settingsResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Create a test conversation
    echo "6. Creating test conversation...\n";
    $conversationResult = makeRequest('conversations.php', 'POST', [
        'title' => 'Test Conversation',
        'user_id' => $loginResult['response']['data']['user']['id']
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Conversation Response Code: " . $conversationResult['code'] . "\n";
    echo "Response: " . json_encode($conversationResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    $conversationId = $conversationResult['response']['data']['id'] ?? '1';
    
    // 7. Test plugin functionality
    echo "7. Testing plugin functionality...\n";
    $testResult = makeRequest('messages.php', 'POST', [
        'conversation_id' => $conversationId,
        'content' => 'Hello, this is a test message'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Message Response Code: " . $testResult['code'] . "\n";
    echo "Response: " . json_encode($testResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 8. Deactivate the plugin
    echo "8. Testing plugin deactivation...\n";
    $deactivateResult = makeRequest('plugins.php', 'POST', [
        'action' => 'deactivate',
        'name' => 'TestMarketplacePlugin'
    ], [
        'Authorization: Bearer ' . $authToken
    ]);
    echo "Deactivation Response Code: " . $deactivateResult['code'] . "\n";
    echo "Response: " . json_encode($deactivateResult['response'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 9. Clean up
    echo "9. Cleaning up...\n";
    unlink($zipFile);
    array_map('unlink', glob($pluginDir . '/*.*'));
    rmdir($pluginDir);
    echo "Test files cleaned up\n";
} 