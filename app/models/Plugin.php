<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class Plugin extends Model {
    protected $table = 'plugins';
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $requiredFields = ['name', 'slug', 'version'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Insert plugin
            $stmt = $this->db->prepare("
                INSERT INTO plugins (
                    name, slug, description, version, author,
                    homepage_url, repository_url, icon_url,
                    is_official, requires_version
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                $data['version'],
                $data['author'] ?? null,
                $data['homepage_url'] ?? null,
                $data['repository_url'] ?? null,
                $data['icon_url'] ?? null,
                $data['is_official'] ?? false,
                $data['requires_version'] ?? null
            ]);
            
            $pluginId = $this->db->lastInsertId();
            
            // If this is a marketplace plugin, create marketplace item
            if (isset($data['price'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO marketplace_items (
                        plugin_id, price, is_featured, status
                    ) VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $pluginId,
                    $data['price'],
                    $data['is_featured'] ?? false,
                    $data['status'] ?? 'draft'
                ]);
            }
            
            $this->db->commit();
            
            Logger::log("Created new plugin", 'INFO', [
                'plugin_id' => $pluginId,
                'name' => $data['name']
            ]);
            
            return $this->getById($pluginId);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating plugin", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function getById($id, $includeMarketplace = false) {
        try {
            $query = "
                SELECT p.*, " . ($includeMarketplace ? "
                    m.price, m.is_featured, m.status, m.downloads, m.rating,
                    (SELECT COUNT(*) FROM user_plugins WHERE plugin_id = p.id) as total_installations,
                    (SELECT COUNT(*) FROM plugin_reviews WHERE plugin_id = p.id) as total_reviews
                " : "''") . "
                FROM plugins p
                " . ($includeMarketplace ? "
                    LEFT JOIN marketplace_items m ON m.plugin_id = p.id
                " : "") . "
                WHERE p.id = ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error getting plugin", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function listMarketplace($filters = [], $page = 1, $limit = 10) {
        try {
            $where = ['m.status = "published"'];
            $params = [];
            
            if (isset($filters['search'])) {
                $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%{$filters['search']}%";
                $params[] = "%{$filters['search']}%";
            }
            
            if (isset($filters['min_rating'])) {
                $where[] = "m.rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            if (isset($filters['is_official'])) {
                $where[] = "p.is_official = ?";
                $params[] = $filters['is_official'];
            }
            
            $offset = ($page - 1) * $limit;
            
            $query = "
                SELECT 
                    p.*,
                    m.price,
                    m.is_featured,
                    m.downloads,
                    m.rating,
                    (SELECT COUNT(*) FROM user_plugins WHERE plugin_id = p.id) as total_installations,
                    (SELECT COUNT(*) FROM plugin_reviews WHERE plugin_id = p.id) as total_reviews
                FROM plugins p
                JOIN marketplace_items m ON m.plugin_id = p.id
                WHERE " . implode(" AND ", $where) . "
                ORDER BY m.is_featured DESC, m.rating DESC, m.downloads DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error listing marketplace", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    public function install($pluginId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get plugin details
            $plugin = $this->getById($pluginId, true);
            if (!$plugin) {
                throw new Exception("Plugin not found");
            }
            
            // Check if already installed
            $stmt = $this->db->prepare("
                SELECT id FROM user_plugins
                WHERE user_id = ? AND plugin_id = ?
            ");
            $stmt->execute([$userId, $pluginId]);
            if ($stmt->fetch()) {
                throw new Exception("Plugin already installed");
            }
            
            // Install plugin
            $stmt = $this->db->prepare("
                INSERT INTO user_plugins (
                    user_id, plugin_id, installed_version
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $pluginId,
                $plugin['version']
            ]);
            
            // Update download count for marketplace items
            if (isset($plugin['price'])) {
                $stmt = $this->db->prepare("
                    UPDATE marketplace_items
                    SET downloads = downloads + 1
                    WHERE plugin_id = ?
                ");
                $stmt->execute([$pluginId]);
            }
            
            $this->db->commit();
            
            Logger::log("Installed plugin", 'INFO', [
                'plugin_id' => $pluginId,
                'user_id' => $userId
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error installing plugin", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function uninstall($pluginId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_plugins
                WHERE user_id = ? AND plugin_id = ?
            ");
            $result = $stmt->execute([$userId, $pluginId]);
            
            Logger::log("Uninstalled plugin", 'INFO', [
                'plugin_id' => $pluginId,
                'user_id' => $userId
            ]);
            
            return $result;
        } catch (Exception $e) {
            Logger::log("Error uninstalling plugin", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getUserPlugins($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    up.is_enabled,
                    up.installed_version,
                    up.installed_at,
                    up.updated_at as last_updated
                FROM plugins p
                JOIN user_plugins up ON up.plugin_id = p.id
                WHERE up.user_id = ?
                ORDER BY up.installed_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error getting user plugins", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    public function toggleEnabled($pluginId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE user_plugins
                SET is_enabled = NOT is_enabled
                WHERE user_id = ? AND plugin_id = ?
            ");
            return $stmt->execute([$userId, $pluginId]);
        } catch (Exception $e) {
            Logger::log("Error toggling plugin", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function all() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    m.price,
                    m.is_featured,
                    m.status as marketplace_status,
                    m.downloads,
                    COALESCE(
                        (SELECT AVG(rating) FROM plugin_reviews WHERE plugin_id = p.id),
                        0
                    ) as average_rating,
                    (SELECT COUNT(*) FROM plugin_reviews WHERE plugin_id = p.id) as total_reviews,
                    (SELECT COUNT(*) FROM user_plugins WHERE plugin_id = p.id) as total_installs
                FROM plugins p
                LEFT JOIN marketplace_items m ON m.plugin_id = p.id
                ORDER BY m.is_featured DESC, p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error('Error fetching all plugins', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 