<?php

require_once __DIR__ . '/Model.php';

class PluginModel extends Model {
    protected $table = 'plugins';
    
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM plugins ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting plugins: " . $e->getMessage());
            return [];
        }
    }
    
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM plugins WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error finding plugin by ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailablePlugins() {
        try {
            $pluginsDir = dirname(__DIR__, 2) . '/plugins';
            $availablePlugins = [];
            
            // Get list of installed plugins
            $stmt = $this->db->query("SELECT name FROM plugins");
            $installedPlugins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (file_exists($pluginsDir)) {
                $pluginDirs = glob($pluginsDir . '/*', GLOB_ONLYDIR);
                foreach ($pluginDirs as $pluginDir) {
                    $pluginName = basename($pluginDir);
                    
                    // Skip if plugin is already installed
                    if (in_array($pluginName, $installedPlugins)) {
                        continue;
                    }
                    
                    $pluginFile = $pluginDir . '/' . $pluginName . '.php';
                    
                    if (file_exists($pluginFile)) {
                        require_once $pluginFile;
                        $className = $pluginName;
                        
                        if (class_exists($className)) {
                            $plugin = new $className();
                            $availablePlugins[] = [
                                'name' => $plugin->getName(),
                                'version' => $plugin->getVersion(),
                                'description' => $plugin->getDescription(),
                                'author' => $plugin->getAuthor(),
                                'is_active' => false // Always false for available plugins
                            ];
                        }
                    }
                }
            }
            
            return $availablePlugins;
        } catch (Exception $e) {
            error_log("Error getting available plugins: " . $e->getMessage());
            return [];
        }
    }
    
    public function isActive($pluginName) {
        try {
            $stmt = $this->db->prepare("SELECT is_active FROM plugins WHERE name = ?");
            $stmt->execute([$pluginName]);
            return $stmt->fetchColumn() === '1';
        } catch (Exception $e) {
            error_log("Error checking plugin status: " . $e->getMessage());
            return false;
        }
    }
    
    public function activate($pluginId) {
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 1 WHERE id = ?");
            return $stmt->execute([$pluginId]);
        } catch (Exception $e) {
            error_log("Error activating plugin: " . $e->getMessage());
            return false;
        }
    }
    
    public function deactivate($pluginId) {
        try {
            $stmt = $this->db->prepare("UPDATE plugins SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$pluginId]);
        } catch (Exception $e) {
            error_log("Error deactivating plugin: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($pluginId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM plugins WHERE id = ?");
            return $stmt->execute([$pluginId]);
        } catch (Exception $e) {
            error_log("Error deleting plugin: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($pluginId, $data) {
        try {
            $allowedFields = ['name', 'description', 'author', 'version', 'is_active'];
            $updates = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $pluginId;
            $sql = "UPDATE plugins SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error updating plugin: " . $e->getMessage());
            return false;
        }
    }
    
    public function install($pluginName) {
        try {
            // Check if plugin already exists
            $stmt = $this->db->prepare("SELECT id FROM plugins WHERE name = ?");
            $stmt->execute([$pluginName]);
            if ($stmt->fetch()) {
                throw new Exception("Plugin already exists");
            }
            
            // Verify plugin files exist
            $pluginsDir = dirname(__DIR__, 2) . '/plugins';
            $pluginDir = $pluginsDir . '/' . $pluginName;
            $pluginFile = $pluginDir . '/' . $pluginName . '.php';
            
            if (!file_exists($pluginDir)) {
                throw new Exception("Plugin directory not found");
            }
            
            if (!file_exists($pluginFile)) {
                throw new Exception("Plugin main file not found");
            }
            
            // Verify plugin class exists
            require_once $pluginFile;
            if (!class_exists($pluginName)) {
                throw new Exception("Plugin class not found");
            }
            
            // Get plugin information
            $plugin = new $pluginName();
            $version = $plugin->getVersion();
            $description = $plugin->getDescription();
            $author = $plugin->getAuthor();
            
            // Create URL-friendly slug from plugin name
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $pluginName));
            
            // Insert new plugin
            $stmt = $this->db->prepare("
                INSERT INTO plugins (name, slug, version, description, author, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            if (!$stmt->execute([$pluginName, $slug, $version, $description, $author])) {
                throw new Exception("Failed to insert plugin into database");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error installing plugin: " . $e->getMessage());
            throw $e; // Re-throw to handle in the controller
        }
    }
} 