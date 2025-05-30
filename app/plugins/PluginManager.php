<?php

require_once dirname(__DIR__, 2) . '/db_config.php';
require_once __DIR__ . '/Plugin.php';
require_once dirname(__DIR__) . '/utils/DatabasePool.php';

class PluginManager {
    private static $instance = null;
    private $plugins = [];
    private $activePlugins = [];
    private $initialized = false;
    private $isTestMode = false;
    private $dbPool;
    
    private function __construct($isTest = false) {
        $this->isTestMode = $isTest;
        $this->dbPool = DatabasePool::getInstance();
        $this->loadPlugins();
    }
    
    public static function getInstance($isTest = false) {
        if (self::$instance === null) {
            self::$instance = new self($isTest);
        }
        return self::$instance;
    }
    
    private function loadPlugins() {
        if ($this->initialized) {
            return;
        }

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
                        $db = $this->dbPool->getConnection();
                        try {
                            $stmt = $db->prepare("SELECT id, is_active FROM plugins WHERE name = ?");
                            $stmt->execute([$pluginName]);
                            $pluginInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$pluginInfo) {
                                error_log("PluginManager: Plugin not found in database, creating record for: " . $pluginName);
                                // Create plugin record with slug
                                $slug = strtolower(str_replace(' ', '-', $pluginName));
                                try {
                                    $stmt = $db->prepare("
                                        INSERT INTO plugins (name, slug, version, is_active, created_at, updated_at)
                                        VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                    ");
                                    $plugin = new $className();
                                    $stmt->execute([$pluginName, $slug, $plugin->getVersion()]);
                                    $pluginId = $db->lastInsertId();
                                    $isActive = true;
                                } catch (PDOException $e) {
                                    error_log("PluginManager ERROR: Failed to create plugin record: " . $e->getMessage());
                                    // If the error is due to duplicate slug, try with a unique slug
                                    if ($e->getCode() == 23000) { // Duplicate entry
                                        $slug = $slug . '-' . uniqid();
                                        $stmt = $db->prepare("
                                            INSERT INTO plugins (name, slug, version, is_active, created_at, updated_at)
                                            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                        ");
                                        $stmt->execute([$pluginName, $slug, $plugin->getVersion()]);
                                        $pluginId = $db->lastInsertId();
                                        $isActive = true;
                                    } else {
                                        throw $e;
                                    }
                                }
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
                        } finally {
                            $this->dbPool->releaseConnection($db);
                        }
                    } catch (Exception $e) {
                        error_log("PluginManager ERROR: Failed to load plugin {$pluginName}: " . $e->getMessage());
                        error_log("PluginManager ERROR: " . $e->getTraceAsString());
                        // Continue with other plugins even if one fails
                        continue;
                    }
                } else {
                    error_log("PluginManager ERROR: Class does not exist: " . $className);
                }
            } else {
                error_log("PluginManager: Plugin file does not exist: " . $pluginFile);
            }
        }
        
        $this->initialized = true;
        error_log("PluginManager: Loaded plugins: " . print_r(array_keys($this->plugins), true));
        error_log("PluginManager: Active plugins: " . print_r(array_keys($this->activePlugins), true));
    }
    
    private function isPluginActive($pluginName) {
        try {
            $db = $this->dbPool->getConnection();
            try {
                $stmt = $db->prepare("SELECT is_active FROM plugins WHERE name = ?");
                $stmt->execute([$pluginName]);
                return $stmt->fetchColumn() === '1';
            } finally {
                $this->dbPool->releaseConnection($db);
            }
        } catch (Exception $e) {
            error_log("PluginManager ERROR: Failed to check plugin status: " . $e->getMessage());
            return false;
        }
    }
    
    public function activatePlugin($pluginName) {
        try {
            error_log("Attempting to activate plugin: " . $pluginName);
            error_log("Available plugins: " . print_r(array_keys($this->plugins), true));
            
            if (isset($this->plugins[$pluginName])) {
                $plugin = $this->plugins[$pluginName];
                error_log("Found plugin instance: " . get_class($plugin));
                
                // Check if plugin exists in database
                $db = $this->dbPool->getConnection();
                try {
                    $stmt = $db->prepare("SELECT id FROM plugins WHERE name = ?");
                    $stmt->execute([$pluginName]);
                    $pluginId = $stmt->fetchColumn();
                    error_log("Plugin ID from database: " . ($pluginId ? $pluginId : 'not found'));
                    
                    if (!$pluginId) {
                        // Plugin doesn't exist in database, create it
                        error_log("Creating new plugin record in database");
                        $slug = strtolower(str_replace(' ', '-', $pluginName));
                        try {
                            $stmt = $db->prepare("
                                INSERT INTO plugins (name, slug, is_active)
                                VALUES (?, ?, 1)
                            ");
                            $stmt->execute([$pluginName, $slug]);
                        } catch (PDOException $e) {
                            if ($e->getCode() == 23000) { // Duplicate entry
                                $slug = $slug . '-' . uniqid();
                                $stmt = $db->prepare("
                                    INSERT INTO plugins (name, slug, is_active)
                                    VALUES (?, ?, 1)
                                ");
                                $stmt->execute([$pluginName, $slug]);
                            } else {
                                throw $e;
                            }
                        }
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
                } finally {
                    $this->dbPool->releaseConnection($db);
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
            $db = $this->dbPool->getConnection();
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
        if ($this->isTestMode) {
            return new MockPlugin();
        }
        
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        }
        
        // Try to load the plugin if it's not loaded yet
        $pluginsDir = dirname(__DIR__, 2) . '/plugins';
        $pluginFile = $pluginsDir . '/' . $name . '/' . $name . '.php';
        
        if (file_exists($pluginFile)) {
            require_once $pluginFile;
            if (class_exists($name)) {
                $plugin = new $name();
                $this->plugins[$name] = $plugin;
                return $plugin;
            }
        }
        
        return null;
    }
    
    public function executeHook($hook, $args = []) {
        foreach ($this->activePlugins as $plugin) {
            try {
                $plugin->executeHook($hook, $args);
            } catch (Exception $e) {
                error_log("Error executing hook {$hook} for plugin " . get_class($plugin) . ": " . $e->getMessage());
                // Continue with other plugins even if one fails
                continue;
            }
        }
    }
} 