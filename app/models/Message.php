<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class Message extends Model {
    protected $table = 'messages';
    
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Unauthorized: User not logged in");
        }
        return $_SESSION['user_id'];
    }
    
    public function getMessages($conversationId) {
        try {
            $userId = $this->checkAuth();
            
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
    
    public function create($conversationId, $content, $role = 'user') {
        try {
            $userId = $this->checkAuth();
            
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
    
    public function delete($messageId, $conversationId) {
        try {
            $userId = $this->checkAuth();
            
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
        $this->checkAuth(); // Ensure user is authenticated
        $stmt = $this->query('SELECT COUNT(*) as count FROM messages');
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Get a message by its ID
     */
    public function getById($messageId) {
        try {
            $userId = $this->checkAuth();
            
            $stmt = $this->db->prepare("
                SELECT m.* 
                FROM messages m
                INNER JOIN conversations c ON c.id = m.conversation_id
                WHERE m.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$messageId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting message by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensure required columns exist in the messages table
     */
    public function ensureColumns() {
        try {
            $this->checkAuth(); // Ensure user is authenticated
            
            // Check if word_count column exists in messages table
            $stmt = $this->db->query("SHOW COLUMNS FROM messages LIKE 'word_count'");
            if ($stmt->rowCount() === 0) {
                // Add word_count column
                $this->db->exec("ALTER TABLE messages ADD COLUMN word_count INT DEFAULT 0 AFTER content");
                error_log("Added word_count column to messages table");
            }

            // Check if message_count column exists in conversations table
            $stmt = $this->db->query("SHOW COLUMNS FROM conversations LIKE 'message_count'");
            if ($stmt->rowCount() === 0) {
                // Add message_count column
                $this->db->exec("ALTER TABLE conversations ADD COLUMN message_count INT DEFAULT 0");
                error_log("Added message_count column to conversations table");
            }

            // Check if total_words column exists in conversations table
            $stmt = $this->db->query("SHOW COLUMNS FROM conversations LIKE 'total_words'");
            if ($stmt->rowCount() === 0) {
                // Add total_words column
                $this->db->exec("ALTER TABLE conversations ADD COLUMN total_words INT DEFAULT 0");
                error_log("Added total_words column to conversations table");
            }
        } catch (Exception $e) {
            error_log("Error ensuring columns: " . $e->getMessage());
            throw $e; // Re-throw the exception to handle it in the calling code
        }
    }
} 