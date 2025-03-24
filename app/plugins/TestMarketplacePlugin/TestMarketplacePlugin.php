<?php
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
}