<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class Message extends Model {
    protected $table = 'messages';
    
    public function getMessages($conversationId, $userId) {
        try {
            // Verify user owns the conversation and get messages in one query
            $stmt = $this->db->prepare("
                SELECT m.* 
                FROM messages m
                INNER JOIN conversations c ON c.id = m.conversation_id
                WHERE m.conversation_id = ? 
                AND c.user_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$conversationId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting messages: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($conversationId, $userId, $content, $role = 'user') {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Verify user owns the conversation
            $stmt = $this->db->prepare("
                SELECT 1 FROM conversations 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$conversationId, $userId]);
            if (!$stmt->fetch()) {
                throw new Exception("User does not own this conversation");
            }
            
            // Calculate word count
            $wordCount = str_word_count(strip_tags($content));
            
            // Create message
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, role, content, word_count) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$conversationId, $role, $content, $wordCount]);
            $messageId = $this->db->lastInsertId();
            
            // Update conversation's updated_at timestamp, message count, and total words
            $stmt = $this->db->prepare("
                UPDATE conversations 
                SET updated_at = CURRENT_TIMESTAMP,
                    message_count = message_count + 1,
                    total_words = total_words + ?
                WHERE id = ?
            ");
            $stmt->execute([$wordCount, $conversationId]);
            
            // Record usage statistics
            require_once __DIR__ . '/UsageStats.php';
            $usageStats = new UsageStats();
            $usageStats->recordUsage($userId, $conversationId, $wordCount, null, $messageId, $role);
            
            // Commit transaction
            $this->db->commit();
            
            return $messageId;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
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

    public function countAll() {
        $stmt = $this->query('SELECT COUNT(*) as count FROM messages');
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
} 