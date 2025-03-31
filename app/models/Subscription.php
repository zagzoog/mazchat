<?php
/**
 * Subscription Model
 * 
 * Handles subscription-related database operations
 */

require_once __DIR__ . '/../config/path_config.php';
require_once __DIR__ . '/../config/db_config.php';

class Subscription {
    private $db;
    private $table = 'memberships';

    public function __construct() {
        try {
            $this->db = getDBConnection();
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("Error in Subscription constructor: " . $e->getMessage());
            throw new Exception("فشل في الاتصال بقاعدة البيانات");
        }
    }

    /**
     * Find subscription by ID
     * 
     * @param string $id Subscription ID
     * @return array|false Subscription data or false if not found
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            throw new Exception("فشل في جلب بيانات الاشتراك");
        }
    }

    /**
     * Update subscription
     * 
     * @param string $id Subscription ID
     * @param array $data Updated subscription data
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['type', 'start_date', 'end_date', 'auto_renew'];
            $updates = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $values[] = $id;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
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
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.username 
                FROM {$this->table} m 
                JOIN users u ON m.user_id = u.id 
                ORDER BY m.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAll: " . $e->getMessage());
            throw new Exception("فشل في جلب قائمة الاشتراكات");
        }
    }

    /**
     * Get total count of subscriptions
     * 
     * @return int Total number of subscriptions
     */
    public function countAll() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in countAll: " . $e->getMessage());
            throw new Exception("فشل في حساب عدد الاشتراكات");
        }
    }

    /**
     * Create new subscription
     * 
     * @param array $data Subscription data
     * @return string|false New subscription ID or false on failure
     */
    public function create($data) {
        try {
            $allowedFields = ['user_id', 'type', 'start_date', 'end_date', 'auto_renew'];
            $fields = ['id'];
            $placeholders = ['UUID()'];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $fields[] = $field;
                    $placeholders[] = '?';
                    $values[] = $value;
                }
            }

            if (empty($fields)) {
                return false;
            }

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            
            if ($stmt->execute($values)) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            throw new Exception("فشل في إنشاء الاشتراك");
        }
    }

    /**
     * Delete subscription
     * 
     * @param string $id Subscription ID
     * @return bool Success status
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            throw new Exception("فشل في حذف الاشتراك");
        }
    }

    /**
     * Get subscriptions by user ID
     * 
     * @param string $userId User ID
     * @return array Array of subscriptions
     */
    public function getByUserId($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getByUserId: " . $e->getMessage());
            throw new Exception("فشل في جلب اشتراكات المستخدم");
        }
    }
} 