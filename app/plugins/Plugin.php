<?php

abstract class Plugin {
    protected $name;
    protected $version;
    protected $description;
    protected $author;
    protected $config;
    protected $db;
    
    public function __construct() {
        $this->config = $this->loadConfig();
        require_once dirname(__DIR__, 2) . '/db_config.php';
        $this->db = getDBConnection();
    }
    
    abstract public function initialize();
    
    public function activate($pluginId = null) {
        // Base activation logic
    }
    
    public function deactivate($pluginId = null) {
        // Base deactivation logic
    }
    
    protected function loadConfig() {
        $configFile = dirname(__DIR__, 2) . '/plugins/' . $this->name . '/config.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }
        return [];
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getVersion() {
        return $this->version;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getAuthor() {
        return $this->author;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    protected function registerHook($hook, $callback, $priority = 10) {
        if (!isset($GLOBALS['hooks'][$hook])) {
            $GLOBALS['hooks'][$hook] = [];
        }
        $GLOBALS['hooks'][$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
    }
    
    public function executeHook($hook, $args = []) {
        if (!isset($GLOBALS['hooks'][$hook])) {
            return;
        }
        
        // Sort hooks by priority
        usort($GLOBALS['hooks'][$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        foreach ($GLOBALS['hooks'][$hook] as $hookData) {
            call_user_func_array($hookData['callback'], $args);
        }
    }
} 