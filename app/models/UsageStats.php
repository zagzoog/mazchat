<?php
require_once __DIR__ . '/Model.php';

class UsageStats extends Model {
    protected $table = 'usage_stats';
    
    public function getMonthlyStats($userId, $month) {
        try {
            // Get total conversations directly from conversations table
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as total_conversations 
                FROM conversations 
                WHERE user_id = ? 
                AND DATE_FORMAT(created_at, "%Y-%m") = ?'
            );
            $stmt->execute([$userId, $month]);
            $conversations = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get total questions and words from usage_stats
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as total_questions, SUM(word_count) as total_words 
                FROM usage_stats 
                WHERE user_id = ? 
                AND DATE_FORMAT(created_at, "%Y-%m") = ?'
            );
            $stmt->execute([$userId, $month]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_conversations' => $conversations['total_conversations'],
                'total_questions' => $stats['total_questions'] ?: 0,
                'total_words' => $stats['total_words'] ?: 0
            ];
        } catch (Exception $e) {
            error_log("Error getting monthly stats: " . $e->getMessage());
            return null;
        }
    }
    
    public function getDailyStats($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT conversation_id) as conversations,
                    COUNT(*) as questions,
                    SUM(word_count) as words
                FROM usage_stats
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC'
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting daily stats: " . $e->getMessage());
            return null;
        }
    }
    
    public function getTopTopics($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT 
                    topic,
                    COUNT(*) as count
                FROM usage_stats
                WHERE user_id = ? 
                AND topic IS NOT NULL
                GROUP BY topic
                ORDER BY count DESC
                LIMIT 5'
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting top topics: " . $e->getMessage());
            return null;
        }
    }
    
    public function recordUsage($userId, $conversationId, $wordCount, $topic = null, $messageId = null) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO usage_stats (user_id, conversation_id, message_id, word_count, topic) 
                VALUES (?, ?, ?, ?, ?)'
            );
            return $stmt->execute([$userId, $conversationId, $messageId, $wordCount, $topic]);
        } catch (Exception $e) {
            error_log("Error recording usage: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getUsageLimit($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT m.type 
                FROM memberships m 
                WHERE m.user_id = ? 
                AND m.end_date >= CURRENT_DATE 
                ORDER BY m.start_date DESC 
                LIMIT 1'
            );
            $stmt->execute([$userId]);
            $membershipType = $stmt->fetchColumn();
            
            if (!$membershipType) {
                return 50; // Default free tier limit
            }
            
            $stmt = $this->db->prepare(
                'SELECT setting_value 
                FROM admin_settings 
                WHERE setting_key = ?'
            );
            $stmt->execute([$membershipType . '_monthly_limit']);
            $limit = $stmt->fetchColumn();
            
            return $limit ?: ($membershipType === 'free' ? 50 : 
                            ($membershipType === 'basic' ? 100 : 999999));
        } catch (Exception $e) {
            error_log("Error getting usage limit: " . $e->getMessage());
            return 50; // Default to free tier limit on error
        }
    }
    
    public function updateStats($conversationId, $messageId, $wordCount) {
        try {
            $this->db->beginTransaction();
            
            // Update conversation stats
            $stmt = $this->db->prepare(
                'UPDATE conversations 
                SET 
                    message_count = message_count + 1,
                    total_words = total_words + ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?'
            );
            $stmt->execute([$wordCount, $conversationId]);
            
            // Update message stats
            $stmt = $this->db->prepare(
                'UPDATE messages 
                SET word_count = ? 
                WHERE id = ?'
            );
            $stmt->execute([$wordCount, $messageId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating stats: " . $e->getMessage());
            throw $e;
        }
    }
} 