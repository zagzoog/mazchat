<?php

require_once dirname(__DIR__, 2) . '/db_config.php';
require_once __DIR__ . '/Plugin.php';

class PluginManager {
    private static $instance = null;
    private $plugins = [];
    private $activePlugins = [];
    
    private function __construct() {
        $this->loadPlugins();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadPlugins() {
        $pluginsDir = dirname(__DIR__, 2) . '/plugins';
        error_log("Loading plugins from directory: " . $pluginsDir);
        
        if (!file_exists($pluginsDir)) {
            mkdir($pluginsDir, 0777, true);
        }
        
        $pluginDirs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
        error_log("Found plugin directories: " . print_r($pluginDirs, true));
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            $pluginFile = $pluginDir . '/' . $pluginName . '.php';
            error_log("Checking plugin file: " . $pluginFile);
            
            if (file_exists($pluginFile)) {
                error_log("Loading plugin file: " . $pluginFile);
                require_once $pluginFile;
                $className = $pluginName;
                
                if (class_exists($className)) {
                    error_log("Creating instance of plugin class: " . $className);
                    $plugin = new $className();
                    $this->plugins[$pluginName] = $plugin;
                    
                    // Check if plugin is active in database
                    if ($this->isPluginActive($pluginName)) {
                        error_log("Plugin is active: " . $pluginName);
                        $this->activePlugins[$pluginName] = $plugin;
                        $plugin->initialize();
                    }
                } else {
                    error_log("Class does not exist: " . $className);
                }
            } else {
                error_log("Plugin file does not exist: " . $pluginFile);
            }
        }
        
        error_log("Loaded plugins: " . print_r(array_keys($this->plugins), true));
        error_log("Active plugins: " . print_r(array_keys($this->activePlugins), true));
    }
    
    private function isPluginActive($pluginName) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT is_active FROM plugins WHERE name = ?");
        $stmt->execute([$pluginName]);
        return $stmt->fetchColumn() === '1';
    }
    
    public function activatePlugin($pluginName) {
        try {
            error_log("Attempting to activate plugin: " . $pluginName);
            error_log("Available plugins: " . print_r(array_keys($this->plugins), true));
            
            if (isset($this->plugins[$pluginName])) {
                $plugin = $this->plugins[$pluginName];
                error_log("Found plugin instance: " . get_class($plugin));
                
                // Check if plugin exists in database
                $db = getDBConnection();
                $stmt = $db->prepare("SELECT id FROM plugins WHERE name = ?");
                $stmt->execute([$pluginName]);
                $pluginId = $stmt->fetchColumn();
                error_log("Plugin ID from database: " . ($pluginId ? $pluginId : 'not found'));
                
                if (!$pluginId) {
                    // Plugin doesn't exist in database, create it
                    error_log("Creating new plugin record in database");
                    $stmt = $db->prepare("
                        INSERT INTO plugins (id, name, is_active)
                        VALUES (UUID(), ?, 1)
                    ");
                    $stmt->execute([$pluginName]);
                } else {
                    // Update existing plugin
                    error_log("Updating existing plugin record in database");
                    $stmt = $db->prepare("
                        UPDATE plugins 
                        SET is_active = 1, 
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE name = ?
                    ");
                    $stmt->execute([$pluginName]);
                }
                
                // Activate the plugin
                error_log("Calling plugin's activate method");
                $plugin->activate();
                
                error_log("Adding plugin to active plugins list");
                $this->activePlugins[$pluginName] = $plugin;
                
                error_log("Initializing plugin");
                $plugin->initialize();
                
                error_log("Plugin activation completed successfully");
                return true;
            }
            error_log("Plugin not found in loaded plugins: " . $pluginName);
            return false;
        } catch (Exception $e) {
            error_log("Error activating plugin: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function deactivatePlugin($pluginName) {
        if (isset($this->activePlugins[$pluginName])) {
            $plugin = $this->activePlugins[$pluginName];
            $plugin->deactivate();
            
            // Update database
            $db = getDBConnection();
            $stmt = $db->prepare("UPDATE plugins SET is_active = 0 WHERE name = ?");
            $stmt->execute([$pluginName]);
            
            unset($this->activePlugins[$pluginName]);
            return true;
        }
        return false;
    }
    
    public function getActivePlugins() {
        return $this->activePlugins;
    }
    
    public function getAllPlugins() {
        return $this->plugins;
    }
    
    public function getPlugin($name) {
        return $this->plugins[$name] ?? null;
    }
    
    public function executeHook($hook, $args = []) {
        foreach ($this->activePlugins as $plugin) {
            $plugin->executeHook($hook, $args);
        }
    }
} 