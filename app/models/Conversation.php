<?php
require_once dirname(__DIR__, 2) . '/db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class Conversation extends Model {
    protected $table = 'conversations';
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    public function getByUserId($userId, $limit = 10, $offset = 0) {
        try {
            // Get conversations with last message and stats
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       m.content as last_message,
                       m.created_at as last_message_time
                FROM conversations c
                LEFT JOIN (
                    SELECT conversation_id, content, created_at
                    FROM messages m1
                    WHERE id = (
                        SELECT MAX(id)
                        FROM messages m2
                        WHERE m2.conversation_id = m1.conversation_id
                    )
                ) m ON m.conversation_id = c.id
                WHERE c.user_id = ?
                ORDER BY c.updated_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM conversations
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            Logger::log("Fetched conversations", 'INFO', [
                'user_id' => $userId,
                'count' => count($conversations),
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            return [
                'conversations' => $conversations,
                'total' => $total,
                'hasMore' => ($offset + $limit) < $total
            ];
        } catch (Exception $e) {
            Logger::log("Error getting conversations", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'conversations' => [],
                'total' => 0,
                'hasMore' => false
            ];
        }
    }
    
    public function create($userId, $pluginId) {
        try {
            // Check monthly conversation limit
            require_once __DIR__ . '/Membership.php';
            $membership = new Membership();
            if (!$membership->checkUsageLimit($userId)) {
                throw new Exception('You have reached your monthly conversation limit. Please upgrade your membership to continue.');
            }
            
            // Verify plugin exists and is active
            $stmt = $this->db->prepare("
                SELECT id FROM plugins 
                WHERE id = ? AND is_active = TRUE
            ");
            $stmt->execute([$pluginId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid or inactive plugin');
            }
            
            // Create new conversation with default title
            $stmt = $this->db->prepare("
                INSERT INTO conversations (user_id, plugin_id, title, created_at, updated_at) 
                VALUES (?, ?, 'محادثة جديدة', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([$userId, $pluginId]);
            $conversationId = $this->db->lastInsertId();
            
            Logger::log("Created new conversation", 'INFO', [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'plugin_id' => $pluginId
            ]);
            
            return $conversationId;
            
        } catch (Exception $e) {
            Logger::log("Error creating conversation", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function getById($id, $userId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name as plugin_name, p.class_name 
            FROM conversations c
            LEFT JOIN plugins p ON c.plugin_id = p.id
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll($userId, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name as plugin_name
            FROM conversations c
            LEFT JOIN plugins p ON c.plugin_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.updated_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function update($id, $userId, $data) {
        $allowedFields = ['title', 'plugin_id'];
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $id;
        $params[] = $userId;
        
        $stmt = $this->db->prepare("
            UPDATE conversations 
            SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute($params);
    }
    
    public function delete($id, $userId) {
        $stmt = $this->db->prepare("
            DELETE FROM conversations 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }
    
    public function countUserConversations($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM conversations 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function countAll() {
        $stmt = $this->query('SELECT COUNT(*) as count FROM conversations');
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getRecentConversations($limit = 5) {
        $sql = 'SELECT c.id, c.title, c.created_at, c.user_id, u.username 
                FROM conversations c 
                JOIN users u ON c.user_id = u.id 
                ORDER BY c.created_at DESC 
                LIMIT ?';
        return $this->query($sql, [$limit])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePluginId($conversationId, $userId, $pluginId) {
        try {
            // Verify plugin exists and is active
            $stmt = $this->db->prepare("
                SELECT id FROM plugins 
                WHERE id = ? AND is_active = TRUE
            ");
            $stmt->execute([$pluginId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid or inactive plugin');
            }
            
            // Update conversation's plugin
            $stmt = $this->db->prepare("
                UPDATE conversations 
                SET plugin_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$pluginId, $conversationId, $userId]);
            
            Logger::log("Updated conversation plugin", 'INFO', [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'plugin_id' => $pluginId
            ]);
            
            return true;
        } catch (Exception $e) {
            Logger::log("Error updating conversation plugin", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 