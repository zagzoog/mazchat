<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class Conversation extends Model {
    protected $table = 'conversations';
    
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
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare(
                'INSERT INTO conversations (user_id, title) VALUES (?, ?)'
            );
            $stmt->execute([$data['user_id'], $data['title']]);
            $conversationId = $this->db->lastInsertId();
            
            // Get the created conversation
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
                WHERE c.id = ?
            ");
            $stmt->execute([$conversationId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->db->commit();
            
            Logger::log("Created new conversation", 'INFO', [
                'conversation_id' => $conversationId,
                'user_id' => $data['user_id'],
                'title' => $data['title']
            ]);
            
            return $conversation;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error creating conversation", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function getById($id, $userId) {
        try {
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
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$id, $userId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            Logger::log("Retrieved conversation", 'INFO', [
                'conversation_id' => $id,
                'user_id' => $userId,
                'found' => !empty($conversation)
            ]);
            
            return $conversation;
        } catch (Exception $e) {
            Logger::log("Error getting conversation", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    public function updateTitle($id, $userId, $title) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare(
                'UPDATE conversations SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
            );
            $result = $stmt->execute([$title, $id, $userId]);
            
            $this->db->commit();
            
            Logger::log("Updated conversation title", 'INFO', [
                'conversation_id' => $id,
                'user_id' => $userId,
                'title' => $title,
                'success' => $result
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error updating conversation title", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function delete($id, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Delete all messages first
            $stmt = $this->db->prepare(
                'DELETE FROM messages WHERE conversation_id = ?'
            );
            $stmt->execute([$id]);
            
            // Then delete the conversation
            $stmt = $this->db->prepare(
                'DELETE FROM conversations WHERE id = ? AND user_id = ?'
            );
            $result = $stmt->execute([$id, $userId]);
            
            $this->db->commit();
            
            Logger::log("Deleted conversation", 'INFO', [
                'conversation_id' => $id,
                'user_id' => $userId,
                'success' => $result
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::log("Error deleting conversation", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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
} 