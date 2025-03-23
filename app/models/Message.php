<?php
require_once __DIR__ . '/Model.php';

class Message extends Model {
    protected $table = 'messages';
    
    public function getMessages($conversationId, $userId) {
        try {
            // Verify user owns the conversation
            $stmt = $this->db->prepare(
                'SELECT 1 FROM conversations WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$conversationId, $userId]);
            if (!$stmt->fetch()) {
                return [];
            }
            
            // Get messages
            $stmt = $this->db->prepare(
                'SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC'
            );
            $stmt->execute([$conversationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting messages: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($conversationId, $userId, $content, $role = 'user') {
        try {
            // Verify user owns the conversation
            $stmt = $this->db->prepare(
                'SELECT 1 FROM conversations WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$conversationId, $userId]);
            if (!$stmt->fetch()) {
                throw new Exception("User does not own this conversation");
            }
            
            // Create message
            $stmt = $this->db->prepare(
                'INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)'
            );
            $stmt->execute([$conversationId, $role, $content]);
            $messageId = $this->db->lastInsertId();
            
            // Update conversation's updated_at timestamp
            $stmt = $this->db->prepare(
                'UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $stmt->execute([$conversationId]);
            
            // Calculate word count
            $wordCount = str_word_count(strip_tags($content));
            
            // Record usage statistics
            require_once __DIR__ . '/UsageStats.php';
            $usageStats = new UsageStats();
            $usageStats->recordUsage($userId, $conversationId, $wordCount, null, $messageId, $role);
            
            // Update message stats
            $usageStats->updateStats($conversationId, $messageId, $wordCount);
            
            return $messageId;
        } catch (Exception $e) {
            error_log("Error creating message: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete($messageId, $conversationId, $userId) {
        try {
            // Verify user owns the conversation
            $stmt = $this->db->prepare(
                'SELECT 1 FROM conversations WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$conversationId, $userId]);
            if (!$stmt->fetch()) {
                throw new Exception("User does not own this conversation");
            }
            
            // Delete message
            $stmt = $this->db->prepare(
                'DELETE FROM messages WHERE id = ? AND conversation_id = ?'
            );
            return $stmt->execute([$messageId, $conversationId]);
        } catch (Exception $e) {
            error_log("Error deleting message: " . $e->getMessage());
            throw $e;
        }
    }
} 