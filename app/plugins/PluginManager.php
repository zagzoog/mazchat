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
        error_log("PluginManager: Loading plugins from directory: " . $pluginsDir);
        
        if (!file_exists($pluginsDir)) {
            mkdir($pluginsDir, 0777, true);
        }
        
        $pluginDirs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
        error_log("PluginManager: Found plugin directories: " . print_r($pluginDirs, true));
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            $pluginFile = $pluginDir . '/' . $pluginName . '.php';
            error_log("PluginManager: Checking plugin file: " . $pluginFile);
            
            if (file_exists($pluginFile)) {
                error_log("PluginManager: Loading plugin file: " . $pluginFile);
                require_once $pluginFile;
                $className = $pluginName;
                
                if (class_exists($className)) {
                    error_log("PluginManager: Creating instance of plugin class: " . $className);
                    try {
                        // Get plugin info from database
                        $db = getDBConnection();
                        $stmt = $db->prepare("SELECT id, is_active FROM plugins WHERE name = ?");
                        $stmt->execute([$pluginName]);
                        $pluginInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$pluginInfo) {
                            error_log("PluginManager: Plugin not found in database, creating record for: " . $pluginName);
                            // Create plugin record
                            $stmt = $db->prepare("
                                INSERT INTO plugins (name, is_active, created_at, updated_at)
                                VALUES (?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                            ");
                            $stmt->execute([$pluginName]);
                            $pluginId = $db->lastInsertId();
                            $isActive = true;
                        } else {
                            $pluginId = $pluginInfo['id'];
                            $isActive = $pluginInfo['is_active'] == 1;
                        }
                        
                        // Create plugin instance
                        error_log("PluginManager: Instantiating plugin with ID: " . $pluginId);
                        $plugin = new $className($pluginId);
                        $this->plugins[$pluginName] = $plugin;
                        
                        if ($isActive) {
                            error_log("PluginManager: Activating plugin: " . $pluginName);
                            // Activate and initialize the plugin
                            $plugin->activate($pluginId);
                            $this->activePlugins[$pluginName] = $plugin;
                            error_log("PluginManager: Initializing plugin: " . $pluginName);
                            $plugin->initialize();
                            error_log("PluginManager: Plugin activated and initialized: " . $pluginName);
                        } else {
                            error_log("PluginManager: Plugin is not active: " . $pluginName);
                        }
                    } catch (Exception $e) {
                        error_log("PluginManager ERROR: Failed to load plugin {$pluginName}: " . $e->getMessage());
                        error_log("PluginManager ERROR: " . $e->getTraceAsString());
                    }
                } else {
                    error_log("PluginManager ERROR: Class does not exist: " . $className);
                }
            } else {
                error_log("PluginManager: Plugin file does not exist: " . $pluginFile);
            }
        }
        
        error_log("PluginManager: Loaded plugins: " . print_r(array_keys($this->plugins), true));
        error_log("PluginManager: Active plugins: " . print_r(array_keys($this->activePlugins), true));
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