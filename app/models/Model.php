<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        try {
            if (!function_exists('getDBConnection')) {
                require_once __DIR__ . '/../../db_config.php';
            }
            $this->db = getDBConnection();
            if (!$this->db) {
                throw new Exception('Database connection failed');
            }
            Logger::info('Database connection established', ['class' => get_class($this)]);
        } catch (Exception $e) {
            Logger::error('Database connection error', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function beginTransaction() {
        try {
            $result = $this->db->beginTransaction();
            Logger::info('Transaction started', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::error('Transaction start error', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function commit() {
        try {
            $result = $this->db->commit();
            Logger::info('Transaction committed', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::error('Transaction commit error', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function rollBack() {
        try {
            $result = $this->db->rollBack();
            Logger::info('Transaction rolled back', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::error('Transaction rollback error', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            Logger::info('Query executed', [
                'class' => get_class($this),
                'sql' => $sql,
                'params' => $params
            ]);
            return $stmt;
        } catch (Exception $e) {
            Logger::error('Query error', [
                'class' => get_class($this),
                'sql' => $sql,
                'params' => $params,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 