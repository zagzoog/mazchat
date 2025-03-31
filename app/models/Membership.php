<?php
require_once __DIR__ . '/Model.php';

class Membership extends Model {
    protected $table = 'memberships';
    
    // Map config types to database types
    private $typeMap = [
        'free' => 'free',
        'silver' => 'basic',
        'gold' => 'premium'
    ];
    
    // Reverse map for displaying types
    private $reverseTypeMap = [
        'free' => 'free',
        'basic' => 'silver',
        'premium' => 'gold'
    ];
    
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $result['type'] = $this->reverseTypeMap[$result['type']] ?? $result['type'];
            }
            return $result;
        } catch (Exception $e) {
            Logger::error("Error getting current membership: " . $e->getMessage());
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
            Logger::error("Error creating free membership: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateMembership($userId, $type, $startDate, $endDate = null) {
        try {
            // Map the type to database type
            $dbType = $this->typeMap[$type] ?? $type;
            
            $stmt = $this->db->prepare(
                'UPDATE memberships 
                SET type = ?, start_date = ?, end_date = ? 
                WHERE user_id = ?'
            );
            return $stmt->execute([$dbType, $startDate, $endDate, $userId]);
        } catch (Exception $e) {
            Logger::error("Error updating membership: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function upgradeMembership($userId, $type, $months = 1) {
        try {
            // Map the type to database type
            $dbType = $this->typeMap[$type] ?? $type;
            
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+{$months} months"));
            
            $stmt = $this->db->prepare(
                'INSERT INTO memberships (user_id, type, start_date, end_date) 
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $dbType, $startDate, $endDate]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'user_id' => $userId,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        } catch (Exception $e) {
            Logger::error("Error upgrading membership: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getMembershipHistory($userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM memberships 
                 WHERE user_id = ? 
                 ORDER BY start_date DESC'
            );
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Map types back to display types
            foreach ($results as &$result) {
                $result['type'] = $this->reverseTypeMap[$result['type']] ?? $result['type'];
            }
            
            return $results;
        } catch (Exception $e) {
            Logger::error("Error getting membership history: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function checkUsageLimit($userId) {
        try {
            $membership = $this->getCurrentMembership($userId);
            if (!$membership) {
                return false;
            }
            
            // Get monthly limit from config
            $config = require __DIR__ . '/../../config.php';
            $monthlyLimit = $config[$membership['type'] . '_monthly_limit'] ?? 50;
            
            // Get current month's usage directly from conversations table
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM conversations 
                 WHERE user_id = ? 
                 AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m")'
            );
            $stmt->execute([$userId]);
            $currentUsage = $stmt->fetchColumn();
            
            Logger::info("Checking conversation limit for user $userId", [
                'current_usage' => $currentUsage,
                'limit' => $monthlyLimit
            ]);
            return $currentUsage < $monthlyLimit;
        } catch (Exception $e) {
            Logger::error("Error checking usage limit: " . $e->getMessage());
            return false;
        }
    }

    public function checkQuestionLimit($userId) {
        try {
            $membership = $this->getCurrentMembership($userId);
            if (!$membership) {
                return false;
            }
            
            // Get question limit from config
            $config = require __DIR__ . '/../../config.php';
            $questionLimit = $config[$membership['type'] . '_question_limit'] ?? 500;
            
            // Get current month's question usage from usage_stats table
            // Only count user messages
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM usage_stats 
                 WHERE user_id = ? 
                 AND message_type = "user"
                 AND DATE_FORMAT(created_at, "%Y-%m") = DATE_FORMAT(CURRENT_DATE, "%Y-%m")'
            );
            $stmt->execute([$userId]);
            $currentUsage = $stmt->fetchColumn();
            
            Logger::info("Checking question limit for user $userId", [
                'current_usage' => $currentUsage,
                'limit' => $questionLimit
            ]);
            return $currentUsage < $questionLimit;
        } catch (Exception $e) {
            Logger::error("Error checking question limit: " . $e->getMessage());
            return false;
        }
    }

    public function getSubscriptionStats() {
        try {
            // Get counts for each subscription type
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN type = 'free' THEN 1 END) as free_count,
                    COUNT(CASE WHEN type = 'basic' THEN 1 END) as silver_count,
                    COUNT(CASE WHEN type = 'premium' THEN 1 END) as gold_count,
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
                        WHEN type = 'basic' THEN 50
                        WHEN type = 'premium' THEN 100
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
            Logger::error("Error getting subscription stats: " . $e->getMessage());
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

    public function findById($id) {
        try {
            Logger::info("Finding membership by ID", ['id' => $id]);
            $stmt = $this->db->prepare('SELECT * FROM memberships WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $result['type'] = $this->reverseTypeMap[$result['type']] ?? $result['type'];
                Logger::info("Found membership", ['id' => $id, 'data' => $result]);
            } else {
                Logger::warning("Membership not found", ['id' => $id]);
            }
            return $result;
        } catch (Exception $e) {
            Logger::error("Error finding membership by ID: " . $e->getMessage(), ['id' => $id]);
            throw $e;
        }
    }
    
    public function update($id, $data) {
        try {
            Logger::info("Updating membership", ['id' => $id, 'data' => $data]);
            $allowedFields = ['type', 'start_date', 'end_date', 'auto_renew'];
            $updates = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if ($key === 'type') {
                        $value = $this->typeMap[$value] ?? $value;
                    }
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                Logger::warning("No valid fields to update", ['id' => $id, 'data' => $data]);
                return false;
            }
            
            $values[] = $id;
            $sql = "UPDATE memberships SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);
            Logger::info("Membership update result", ['id' => $id, 'success' => $result]);
            return $result;
        } catch (Exception $e) {
            Logger::error("Error updating membership: " . $e->getMessage(), ['id' => $id, 'data' => $data]);
            throw $e;
        }
    }
} 