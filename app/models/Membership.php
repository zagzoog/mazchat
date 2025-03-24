<?php
require_once __DIR__ . '/Model.php';

class Membership extends Model {
    protected $table = 'memberships';
    
    public function getCurrentMembership($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM memberships 
                WHERE user_id = ? 
                AND start_date <= CURRENT_DATE 
                AND (end_date IS NULL OR end_date >= CURRENT_DATE)
                ORDER BY start_date DESC 
                LIMIT 1'
            );
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting current membership: " . $e->getMessage());
            return null;
        }
    }
    
    public function createFreeMembership($userId) {
        try {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 year'));
            
            $stmt = $this->db->prepare(
                'INSERT INTO memberships (user_id, type, start_date, end_date) 
                VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, 'free', $startDate, $endDate]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'user_id' => $userId,
                'type' => 'free',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        } catch (Exception $e) {
            error_log("Error creating free membership: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateMembership($userId, $type, $startDate, $endDate = null) {
        try {
            $stmt = $this->db->prepare(
                'UPDATE memberships 
                SET type = ?, start_date = ?, end_date = ? 
                WHERE user_id = ?'
            );
            return $stmt->execute([$type, $startDate, $endDate, $userId]);
        } catch (Exception $e) {
            error_log("Error updating membership: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function upgradeMembership($userId, $type, $months = 1) {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$months} months"));
        
        $stmt = $this->db->prepare(
            'INSERT INTO memberships (user_id, type, start_date, end_date) 
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $startDate, $endDate]);
        
        return [
            'id' => $this->db->lastInsertId(),
            'user_id' => $userId,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }
    
    public function getMembershipHistory($userId) {
        $stmt = $this->db->prepare(
            'SELECT * FROM memberships 
             WHERE user_id = ? 
             ORDER BY start_date DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkUsageLimit($userId) {
        $membership = $this->getCurrentMembership($userId);
        $db = getDBConnection();
        
        // Get monthly limit from settings
        $stmt = $db->prepare(
            'SELECT setting_value FROM admin_settings WHERE setting_key = ?'
        );
        $stmt->execute([$membership['type'] . '_monthly_limit']);
        $monthlyLimit = $stmt->fetchColumn();
        
        if (!$monthlyLimit) {
            // Set default limits if not found in settings
            $monthlyLimit = $membership['type'] === 'free' ? 50 : 
                           ($membership['type'] === 'basic' ? 100 : 999999);
        }
        
        // Get current month's usage directly from conversations table
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM conversations 
             WHERE user_id = ? 
             AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m")'
        );
        $stmt->execute([$userId]);
        $currentUsage = $stmt->fetchColumn();
        
        error_log("Checking conversation limit for user $userId: current usage = $currentUsage, limit = $monthlyLimit");
        return $currentUsage < $monthlyLimit;
    }

    public function checkQuestionLimit($userId) {
        $membership = $this->getCurrentMembership($userId);
        $db = getDBConnection();
        
        // Get question limit from settings
        $stmt = $db->prepare(
            'SELECT setting_value FROM admin_settings WHERE setting_key = ?'
        );
        $stmt->execute([$membership['type'] . '_question_limit']);
        $questionLimit = $stmt->fetchColumn();
        
        if (!$questionLimit) {
            // Set default limits if not found in settings
            $questionLimit = $membership['type'] === 'free' ? 500 : 
                           ($membership['type'] === 'basic' ? 2000 : 999999);
        }
        
        // Get current month's question usage from usage_stats table
        // Only count user messages
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM usage_stats 
             WHERE user_id = ? 
             AND message_type = "user"
             AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m")'
        );
        $stmt->execute([$userId]);
        $currentUsage = $stmt->fetchColumn();
        
        error_log("Checking question limit for user $userId: current usage = $currentUsage, limit = $questionLimit");
        return $currentUsage < $questionLimit;
    }

    public function getSubscriptionStats() {
        try {
            // Get counts for each subscription type
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN type = 'free' THEN 1 END) as free_count,
                    COUNT(CASE WHEN type = 'silver' THEN 1 END) as silver_count,
                    COUNT(CASE WHEN type = 'gold' THEN 1 END) as gold_count,
                    COUNT(*) as total_active,
                    COUNT(CASE WHEN end_date < CURRENT_DATE THEN 1 END) as expired
                FROM memberships 
                WHERE start_date <= CURRENT_DATE 
                AND (end_date IS NULL OR end_date >= CURRENT_DATE)
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate monthly revenue
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE 
                        WHEN type = 'silver' THEN 50
                        WHEN type = 'gold' THEN 100
                        ELSE 0
                    END) as monthly_revenue
                FROM memberships 
                WHERE start_date <= CURRENT_DATE 
                AND (end_date IS NULL OR end_date >= CURRENT_DATE)
            ");
            $stmt->execute();
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC);

            $stats['monthly_revenue'] = $revenue['monthly_revenue'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting subscription stats: " . $e->getMessage());
            return [
                'free_count' => 0,
                'silver_count' => 0,
                'gold_count' => 0,
                'total_active' => 0,
                'expired' => 0,
                'monthly_revenue' => 0
            ];
        }
    }
} 