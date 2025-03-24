<?php
require_once __DIR__ . '/Model.php';

class UsageStats extends Model {
    protected $table = 'usage_stats';
    
    public function getAllStats($userId, $month) {
        try {
            // Get monthly stats
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT conversation_id) as total_conversations,
                    COUNT(*) as total_questions,
                    SUM(word_count) as total_words
                FROM usage_stats
                WHERE user_id = ? 
                AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ");
            $stmt->execute([$userId, $month]);
            $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get daily stats for the last 30 days
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as questions,
                    SUM(word_count) as words
                FROM usage_stats
                WHERE user_id = ?
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$userId]);
            $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get top topics
            $stmt = $this->db->prepare("
                SELECT 
                    topic,
                    COUNT(*) as count
                FROM usage_stats
                WHERE user_id = ?
                AND topic IS NOT NULL
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY topic
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $topTopics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'monthly' => $monthlyStats ?: [
                    'total_conversations' => 0,
                    'total_questions' => 0,
                    'total_words' => 0
                ],
                'daily' => $dailyStats,
                'top_topics' => $topTopics
            ];
        } catch (Exception $e) {
            error_log("Error getting all stats: " . $e->getMessage());
            return null;
        }
    }
    
    public function recordUsage($userId, $conversationId, $wordCount, $topic = null, $messageId = null, $messageType = 'user') {
        try {
            // Ensure we have a non-null value for question
            $question = $topic ?? 'General Query'; // Default value if topic is null
            
            $stmt = $this->db->prepare("
                INSERT INTO usage_stats (
                    user_id, 
                    conversation_id, 
                    message_id, 
                    word_count, 
                    topic, 
                    message_type,
                    question
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $conversationId, $messageId, $wordCount, $topic, $messageType, $question]);
        } catch (Exception $e) {
            error_log("Error recording usage: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateStats($conversationId, $messageId, $wordCount) {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversations 
                SET total_words = total_words + ?
                WHERE id = ?
            ");
            return $stmt->execute([$wordCount, $conversationId]);
        } catch (Exception $e) {
            error_log("Error updating stats: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure required columns exist in the usage_stats table
     */
    public function ensureColumns() {
        try {
            // Check if message_id column exists
            $stmt = $this->db->query("SHOW COLUMNS FROM usage_stats LIKE 'message_id'");
            if ($stmt->rowCount() === 0) {
                // Add message_id column
                $this->db->exec("ALTER TABLE usage_stats ADD COLUMN message_id INT DEFAULT NULL");
                error_log("Added message_id column to usage_stats table");
            }

            // Check if message_type column exists
            $stmt = $this->db->query("SHOW COLUMNS FROM usage_stats LIKE 'message_type'");
            if ($stmt->rowCount() === 0) {
                // Add message_type column
                $this->db->exec("ALTER TABLE usage_stats ADD COLUMN message_type VARCHAR(10) DEFAULT 'user'");
                error_log("Added message_type column to usage_stats table");
            }

            // Check if question column exists
            $stmt = $this->db->query("SHOW COLUMNS FROM usage_stats LIKE 'question'");
            if ($stmt->rowCount() === 0) {
                // Add question column with a default value
                $this->db->exec("ALTER TABLE usage_stats ADD COLUMN question TEXT NOT NULL DEFAULT 'General Query'");
                error_log("Added question column to usage_stats table");
            }
        } catch (Exception $e) {
            error_log("Error ensuring columns: " . $e->getMessage());
            throw $e;
        }
    }
} 