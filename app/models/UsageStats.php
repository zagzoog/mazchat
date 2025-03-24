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
            $stmt = $this->db->prepare("
                INSERT INTO usage_stats (
                    user_id, 
                    conversation_id, 
                    message_id, 
                    word_count, 
                    topic, 
                    message_type
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $conversationId, $messageId, $wordCount, $topic, $messageType]);
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
} 