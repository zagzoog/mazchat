<?php
/**
 * Subscription Model
 * 
 * Handles subscription-related database operations
 */

require_once __DIR__ . '/../../db_config.php';

class Subscription {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Find subscription by ID
     * 
     * @param int $id Subscription ID
     * @return array|false Subscription data or false if not found
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update subscription
     * 
     * @param int $id Subscription ID
     * @param array $data Updated subscription data
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE subscriptions 
                SET plan = ?, 
                    start_date = ?, 
                    end_date = ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([
                $data['plan'],
                $data['start_date'],
                $data['end_date'],
                $data['status'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating subscription: " . $e->getMessage());
            throw new Exception("فشل في تحديث الاشتراك");
        }
    }

    /**
     * Get all subscriptions with pagination
     * 
     * @param int $limit Number of records per page
     * @param int $offset Starting offset
     * @return array Array of subscriptions
     */
    public function getAll($limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username 
            FROM subscriptions s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count of subscriptions
     * 
     * @return int Total number of subscriptions
     */
    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM subscriptions");
        return $stmt->fetchColumn();
    }

    /**
     * Create new subscription
     * 
     * @param array $data Subscription data
     * @return int|false New subscription ID or false on failure
     */
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions (
                    user_id, plan, start_date, end_date, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $data['user_id'],
                $data['plan'],
                $data['start_date'],
                $data['end_date'],
                $data['status'] ?? 'active'
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating subscription: " . $e->getMessage());
            throw new Exception("فشل في إنشاء الاشتراك");
        }
    }

    /**
     * Delete subscription
     * 
     * @param int $id Subscription ID
     * @return bool Success status
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM subscriptions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting subscription: " . $e->getMessage());
            throw new Exception("فشل في حذف الاشتراك");
        }
    }
} 