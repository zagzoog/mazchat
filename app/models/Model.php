<?php
require_once __DIR__ . '/../../db_config.php';

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        require_once __DIR__ . '/../utils/Logger.php';
        try {
            $this->db = getDBConnection();
            if (!$this->db) {
                throw new Exception('Database connection failed');
            }
            Logger::log('Database connection established', 'INFO', ['class' => get_class($this)]);
        } catch (Exception $e) {
            Logger::log('Database connection error', 'ERROR', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function beginTransaction() {
        try {
            $result = $this->db->beginTransaction();
            Logger::log('Transaction started', 'INFO', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::log('Transaction start error', 'ERROR', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function commit() {
        try {
            $result = $this->db->commit();
            Logger::log('Transaction committed', 'INFO', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::log('Transaction commit error', 'ERROR', [
                'class' => get_class($this),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function rollBack() {
        try {
            $result = $this->db->rollBack();
            Logger::log('Transaction rolled back', 'INFO', ['class' => get_class($this)]);
            return $result;
        } catch (Exception $e) {
            Logger::log('Transaction rollback error', 'ERROR', [
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
            Logger::log('Query executed', 'INFO', [
                'class' => get_class($this),
                'sql' => $sql,
                'params' => $params
            ]);
            return $stmt;
        } catch (Exception $e) {
            Logger::log('Query error', 'ERROR', [
                'class' => get_class($this),
                'sql' => $sql,
                'params' => $params,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 