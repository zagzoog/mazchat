<?php
require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class SameInSameOut extends Plugin {
    private $pluginId;

    public function __construct($pluginId = null) {
        $this->name = 'SameInSameOut';
        $this->version = '1.0.0';
        $this->description = 'A simple plugin that returns the exact same text sent by the user';
        $this->author = 'System';
        $this->pluginId = $pluginId;
        
        parent::__construct();
    }
    
    public function initialize() {
        $this->hooks = [];
        
        // Register the hook to process messages after they are sent
        $this->registerHook('before_send_message', [$this, 'processMessage']);
        $this->registerHook('after_send_message', [$this, 'processMessage']);

    }
    
    public function activate($pluginId = null) {
        if ($pluginId) {
            $this->pluginId = $pluginId;
        }
        
        if (!$this->pluginId) {
            throw new Exception("Plugin ID is required for activation");
        }
        
        parent::activate($this->pluginId);
        $this->initialize();
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
    }
    
    public function processMessage($data) {


        $userMessage = $data['content'];

        return $userMessage;
    }
} 