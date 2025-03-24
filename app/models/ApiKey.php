<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class ApiKey extends Model {
    protected $table = 'api_keys';
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate a unique API key
            $apiKey = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare("
                INSERT INTO api_keys (user_id, name, description, api_key) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_id'],
                $data['name'],
                $data['description'] ?? null,
                $apiKey
            ]);
            
            $id = $this->db->lastInsertId();
            
            // Get the created API key
            $stmt = $this->db->prepare("
                SELECT * FROM api_keys WHERE id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->db->commit();
            
            Logger::log("Created new API key", 'INFO', [
                'api_key_id' => $id,
                'user_id' => $data['user_id']
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating API key", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function getByUserId($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, is_active, last_used_at, created_at, updated_at
                FROM api_keys 
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error getting API keys", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    public function toggleActive($id, $userId) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE api_keys 
                SET is_active = NOT is_active 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$id, $userId]);
            
            if ($result) {
                $stmt = $this->db->prepare("
                    SELECT * FROM api_keys WHERE id = ?
                ");
                $stmt->execute([$id]);
                $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $this->db->commit();
            
            Logger::log("Toggled API key status", 'INFO', [
                'api_key_id' => $id,
                'user_id' => $userId,
                'success' => $result
            ]);
            
            return $apiKey ?? null;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error toggling API key status", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function delete($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM api_keys 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$id, $userId]);
            
            Logger::log("Deleted API key", 'INFO', [
                'api_key_id' => $id,
                'user_id' => $userId,
                'success' => $result
            ]);
            
            return $result;
        } catch (Exception $e) {
            Logger::log("Error deleting API key", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function updateLastUsed($apiKey) {
        try {
            $stmt = $this->db->prepare("
                UPDATE api_keys 
                SET last_used_at = CURRENT_TIMESTAMP 
                WHERE api_key = ? AND is_active = TRUE
            ");
            return $stmt->execute([$apiKey]);
        } catch (Exception $e) {
            Logger::log("Error updating API key last used", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function validateKey($apiKey) {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id 
                FROM api_keys 
                WHERE api_key = ? AND is_active = TRUE
            ");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->updateLastUsed($apiKey);
            }
            
            return $result ? $result['user_id'] : null;
        } catch (Exception $e) {
            Logger::log("Error validating API key", 'ERROR', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
} 