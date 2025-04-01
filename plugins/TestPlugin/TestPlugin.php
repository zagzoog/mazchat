<?php

class TestPlugin extends Plugin {
    public function __construct($pluginId = null) {
        parent::__construct();
        
        $this->name = 'TestPlugin';
        $this->version = '1.0.0';
        $this->description = 'A test plugin for unit testing';
        $this->author = 'Test Author';
    }
    
    public function initialize() {
        error_log("TestPlugin: Initializing plugin");
        // Register any hooks here
        $this->registerHook('before_send_message', [$this, 'processMessage']);
        $this->registerHook('after_send_message', [$this, 'processMessage']);
        error_log("TestPlugin: Initialization completed");
    }
    
    public function processMessage($message) {
        error_log("TestPlugin: Processing message: " . print_r($message, true));
        return "Test Plugin Response: " . $message['content'];
    }
} 