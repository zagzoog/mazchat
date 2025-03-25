<?php

abstract class Plugin {
    protected $name;
    protected $version;
    protected $description;
    protected $author;
    protected $config;
    protected $db;
    protected $hooks = [];  // Store hooks at instance level
    
    public function __construct() {
        error_log(get_class($this) . ": Constructing plugin");
        $this->config = $this->loadConfig();
        require_once dirname(__DIR__, 2) . '/db_config.php';
        $this->db = getDBConnection();
    }
    
    abstract public function initialize();
    
    public function activate($pluginId = null) {
        // Base activation logic
        error_log("Plugin {$this->name}: Activated with ID " . ($pluginId ?? 'null'));
    }
    
    public function deactivate($pluginId = null) {
        // Base deactivation logic
        error_log("Plugin {$this->name}: Deactivated");
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
    
    public function getHooks() {
        return $this->hooks;
    }
    
    protected function registerHook($hook, $callback, $priority = 10) {
        error_log("Plugin {$this->name}: Registering hook {$hook}");
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }
        $this->hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        error_log("Plugin {$this->name}: Hooks after registration: " . print_r($this->hooks, true));
    }
    
    public function executeHook($hook, $args = []) {
        error_log("Plugin {$this->name}: Executing hook {$hook}");
        error_log("Plugin {$this->name}: Available hooks: " . print_r($this->hooks, true));
        
        if (!isset($this->hooks[$hook])) {
            error_log("Plugin {$this->name}: No handlers registered for hook {$hook}");
            return null;
        }
        
        // Sort hooks by priority
        usort($this->hooks[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        $lastResult = null;
        foreach ($this->hooks[$hook] as $hookData) {
            error_log("Plugin {$this->name}: Calling hook handler for {$hook}");
            try {
                $lastResult = call_user_func_array($hookData['callback'], $args);
                error_log("Plugin {$this->name}: Hook handler executed successfully with result: " . ($lastResult ?: 'null'));
            } catch (Exception $e) {
                error_log("Plugin {$this->name}: Error executing hook handler: " . $e->getMessage());
                error_log("Plugin {$this->name}: Stack trace: " . $e->getTraceAsString());
            }
        }
        return $lastResult;
    }
} 