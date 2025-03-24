<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    
    public function findByUsername($username) {
        $stmt = $this->query('SELECT id, username, email, password, role FROM users WHERE username = ?', [$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByEmail($email) {
        $stmt = $this->query('SELECT id, username, email, password, role FROM users WHERE email = ?', [$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $stmt = $this->query('SELECT id, username, email, password, role FROM users WHERE id = ?', [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($username, $email, $password, $role = 'user') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->query(
            'INSERT INTO users (id, username, email, password, role) VALUES (UUID(), ?, ?, ?, ?)',
            [$username, $email, $hashedPassword, $role]
        );
        return $this->db->lastInsertId();
    }
    
    public function updateProfile($userId, $username, $email) {
        return $this->query(
            'UPDATE users SET username = ?, email = ? WHERE id = ?',
            [$username, $email, $userId]
        );
    }
    
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->query(
            'UPDATE users SET password = ? WHERE id = ?',
            [$hashedPassword, $userId]
        );
    }
    
    public function verifyPassword($userId, $password) {
        $user = $this->findById($userId);
        return $user && password_verify($password, $user['password']);
    }
    
    public function isAdmin($userId) {
        $user = $this->findById($userId);
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }
    
    public function updateLastLogin($userId) {
        try {
            return $this->query(
                'UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?',
                [$userId]
            );
        } catch (PDOException $e) {
            // If the last_login column doesn't exist, log the error but don't fail
            Logger::log("Error updating last_login: " . $e->getMessage(), 'WARNING');
            return false;
        }
    }

    public function getAll($limit = null, $offset = null) {
        $sql = 'SELECT id, username, email, role, created_at FROM users';
        if ($limit !== null) {
            $sql .= ' LIMIT ?';
            if ($offset !== null) {
                $sql .= ' OFFSET ?';
                return $this->query($sql, [$limit, $offset])->fetchAll(PDO::FETCH_ASSOC);
            }
            return $this->query($sql, [$limit])->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll() {
        $stmt = $this->query('SELECT COUNT(*) as count FROM users');
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getRecentUsers($limit = 5) {
        $sql = 'SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT ?';
        return $this->query($sql, [$limit])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsersWithSubscriptions() {
        try {
            $sql = "
                SELECT u.*, 
                       m.type as membership_type,
                       m.start_date,
                       m.end_date
                FROM users u
                LEFT JOIN (
                    SELECT user_id, type, start_date, end_date
                    FROM memberships m1
                    WHERE id = (
                        SELECT MAX(id)
                        FROM memberships m2
                        WHERE m2.user_id = m1.user_id
                    )
                ) m ON m.user_id = u.id
                ORDER BY u.created_at DESC
            ";
            return $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::log("Error getting users with subscriptions", 'ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
} 