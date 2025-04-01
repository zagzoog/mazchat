<?php
require_once __DIR__ . '/../config/database_constants.php';
require_once __DIR__ . '/Logger.php';

class DatabasePool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10; // Increased from 5
    private $connectionTimeout = 10; // Increased from 3
    private $lastCleanup = 0;
    private $cleanupInterval = 60; // Increased from 30
    
    private function __construct() {
        // Initialize with two connections
        for ($i = 0; $i < 2; $i++) {
            $this->connections[] = [
                'connection' => $this->createConnection(),
                'in_use' => false,
                'last_used' => time()
            ];
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function createConnection() {
        try {
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
                throw new Exception("Database configuration constants are not defined");
            }

            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_TIMEOUT => 10, // Increased from 3
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $connection;
            
        } catch (PDOException $e) {
            Logger::error("Database connection failed", [
                'error' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME,
                'code' => $e->getCode()
            ]);
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        // Run cleanup if needed
        $this->cleanupIfNeeded();
        
        // Try to find an available connection
        foreach ($this->connections as $key => $conn) {
            if (!$conn['in_use']) {
                $this->connections[$key]['in_use'] = true;
                $this->connections[$key]['last_used'] = time();
                return $conn['connection'];
            }
        }
        
        // If no available connection and under max limit, create new one
        if (count($this->connections) < $this->maxConnections) {
            try {
                $conn = $this->createConnection();
                $key = count($this->connections);
                $this->connections[] = [
                    'connection' => $conn,
                    'in_use' => true,
                    'last_used' => time()
                ];
                return $conn;
            } catch (Exception $e) {
                Logger::error("Failed to create new connection", ['error' => $e->getMessage()]);
                throw $e;
            }
        }
        
        // If we can't create a new connection, wait for an existing one
        return $this->waitForConnection();
    }
    
    public function releaseConnection($connection) {
        foreach ($this->connections as $key => $conn) {
            if ($conn['connection'] === $connection) {
                $this->connections[$key]['in_use'] = false;
                $this->connections[$key]['last_used'] = time();
                break;
            }
        }
    }
    
    private function waitForConnection() {
        $startTime = time();
        $attempts = 0;
        $maxAttempts = 200; // 10 seconds with 50ms sleep
        
        while ($attempts < $maxAttempts) {
            foreach ($this->connections as $key => $conn) {
                if (!$conn['in_use']) {
                    $this->connections[$key]['in_use'] = true;
                    $this->connections[$key]['last_used'] = time();
                    return $conn['connection'];
                }
            }
            usleep(50000); // Sleep for 50ms
            $attempts++;
        }
        
        Logger::error("Connection wait timeout", ['timeout' => $this->connectionTimeout]);
        throw new Exception('Timeout waiting for database connection');
    }
    
    private function cleanupIfNeeded() {
        $now = time();
        if ($now - $this->lastCleanup >= $this->cleanupInterval) {
            $this->cleanup();
            $this->lastCleanup = $now;
        }
    }
    
    public function cleanup() {
        $now = time();
        $cleaned = false;
        
        foreach ($this->connections as $key => $conn) {
            // Close connections that haven't been used for more than 1 minute
            if (!$conn['in_use'] && ($now - $conn['last_used']) > 60) {
                unset($this->connections[$key]);
                $cleaned = true;
            }
        }
        
        // Reindex array if we removed any connections
        if ($cleaned) {
            $this->connections = array_values($this->connections);
        }
    }
    
    public function __destruct() {
        // Close all connections when the pool is destroyed
        foreach ($this->connections as $conn) {
            $conn['connection'] = null;
        }
        $this->connections = [];
    }
} 