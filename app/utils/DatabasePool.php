<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/Logger.php';

class DatabasePool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $connectionTimeout = 2; // Reduced from 5 to 2 seconds
    
    private function __construct() {
        Logger::info("DatabasePool instance created");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function createConnection() {
        Logger::info("Attempting to create new database connection");
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            Logger::info("DSN: " . $dsn);
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true // Enable persistent connections
            ];
            
            $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            Logger::info("Database connection created successfully");
            return $connection;
            
        } catch (PDOException $e) {
            Logger::error("Database connection failed", [
                'error' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME,
                'user' => DB_USER
            ]);
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        Logger::info("Getting database connection", ['current_connections' => count($this->connections)]);
        
        // Try to find an available connection
        foreach ($this->connections as $key => $conn) {
            if (!$conn['in_use']) {
                Logger::info("Reusing existing connection", ['connection_id' => $key]);
                $this->connections[$key]['in_use'] = true;
                $this->connections[$key]['last_used'] = time();
                return $conn['connection'];
            }
        }
        
        // If no available connection and under max limit, create new one
        if (count($this->connections) < $this->maxConnections) {
            try {
                Logger::info("Creating new connection", ['current_count' => count($this->connections)]);
                $conn = $this->createConnection();
                $key = count($this->connections);
                $this->connections[] = [
                    'connection' => $conn,
                    'in_use' => true,
                    'last_used' => time()
                ];
                Logger::info("New connection created successfully", ['connection_id' => $key]);
                return $conn;
            } catch (Exception $e) {
                Logger::error("Failed to create new connection", ['error' => $e->getMessage()]);
                throw $e;
            }
        }
        
        // If we can't create a new connection, wait for an existing one
        Logger::info("Maximum connections reached, waiting for available connection");
        return $this->waitForConnection();
    }
    
    public function releaseConnection($connection) {
        foreach ($this->connections as $key => $conn) {
            if ($conn['connection'] === $connection) {
                Logger::info("Releasing connection", ['connection_id' => $key]);
                $this->connections[$key]['in_use'] = false;
                $this->connections[$key]['last_used'] = time();
                break;
            }
        }
    }
    
    private function waitForConnection() {
        $startTime = time();
        Logger::info("Starting to wait for available connection");
        
        while (time() - $startTime < $this->connectionTimeout) {
            foreach ($this->connections as $key => $conn) {
                if (!$conn['in_use']) {
                    Logger::info("Found available connection while waiting", ['connection_id' => $key]);
                    $this->connections[$key]['in_use'] = true;
                    $this->connections[$key]['last_used'] = time();
                    return $conn['connection'];
                }
            }
            usleep(100000); // Sleep for 100ms
        }
        
        Logger::error("Connection wait timeout", ['timeout' => $this->connectionTimeout]);
        throw new Exception('Timeout waiting for database connection');
    }
    
    public function cleanup() {
        $now = time();
        Logger::info("Running connection cleanup");
        
        foreach ($this->connections as $key => $conn) {
            // Close connections that haven't been used for more than 5 minutes
            if (!$conn['in_use'] && ($now - $conn['last_used']) > 300) {
                Logger::info("Closing idle connection", ['connection_id' => $key]);
                unset($this->connections[$key]);
            }
        }
        
        Logger::info("Cleanup complete", ['remaining_connections' => count($this->connections)]);
    }
} 