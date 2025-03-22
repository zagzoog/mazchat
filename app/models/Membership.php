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
        
        // Get current month's usage
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM conversations WHERE user_id = ? AND created_at >= DATE_FORMAT(CURRENT_DATE, "%Y-%m-01")'
        );
        $stmt->execute([$userId]);
        $currentUsage = $stmt->fetchColumn();
        
        return $currentUsage < $monthlyLimit;
    }
} 