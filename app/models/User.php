<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    
    public function findByUsername($username) {
        $stmt = $this->query('SELECT * FROM users WHERE username = ?', [$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByEmail($email) {
        $stmt = $this->query('SELECT * FROM users WHERE email = ?', [$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $stmt = $this->query('SELECT * FROM users WHERE id = ?', [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->query(
            'INSERT INTO users (username, email, password) VALUES (?, ?, ?)',
            [$username, $email, $hashedPassword]
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
} 