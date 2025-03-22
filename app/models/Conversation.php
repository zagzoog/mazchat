<?php
require_once __DIR__ . '/Model.php';

class Conversation extends Model {
    protected $table = 'conversations';
    
    public function getConversations($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT c.*, 
                    (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM conversations c 
                WHERE c.user_id = ? 
                ORDER BY c.updated_at DESC'
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting conversations: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($userId, $title = null) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO conversations (user_id, title) VALUES (?, ?)'
            );
            $stmt->execute([$userId, $title]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating conversation: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConversation($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM conversations WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$conversationId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting conversation: " . $e->getMessage());
            return null;
        }
    }
    
    public function updateTitle($conversationId, $userId, $title) {
        try {
            $stmt = $this->db->prepare(
                'UPDATE conversations SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
            );
            return $stmt->execute([$title, $conversationId, $userId]);
        } catch (Exception $e) {
            error_log("Error updating conversation title: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'DELETE FROM conversations WHERE id = ? AND user_id = ?'
            );
            return $stmt->execute([$conversationId, $userId]);
        } catch (Exception $e) {
            error_log("Error deleting conversation: " . $e->getMessage());
            throw $e;
        }
    }
} 