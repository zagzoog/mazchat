<?php
class Logger {
    private static $instance = null;
    private static $config = null;
    private static $logFile = null;
    
    private function __construct() {
        try {
            self::$config = require_once __DIR__ . '/../../config.php';
            self::$logFile = __DIR__ . '/../../logs/php_errors.log';
            
            // Ensure config is an array
            if (!is_array(self::$config)) {
                self::$config = [
                    'debug_logging' => true,
                    'development_mode' => true
                ];
            }
            
            // Ensure log directory exists
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            // Ensure log file is writable
            if (!is_writable($logDir)) {
                throw new Exception("Log directory is not writable: $logDir");
            }
            
            if (file_exists(self::$logFile) && !is_writable(self::$logFile)) {
                throw new Exception("Log file is not writable: " . self::$logFile);
            }
        } catch (Exception $e) {
            error_log("Logger initialization failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function log($message, $type = 'info', $context = []) {
        try {
            if (self::$instance === null) {
                self::getInstance();
            }
            
            // Ensure config is an array and has required keys
            if (!is_array(self::$config)) {
                self::$config = [
                    'debug_logging' => true,
                    'development_mode' => true
                ];
            }
            
            if (!isset(self::$config['debug_logging']) || !self::$config['debug_logging']) {
                return;
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$type] $message";
            
            // Add context if provided
            if (!empty($context)) {
                $logMessage .= " " . json_encode($context, JSON_UNESCAPED_UNICODE);
            }
            
            $logMessage .= PHP_EOL;
            
            if (file_put_contents(self::$logFile, $logMessage, FILE_APPEND) === false) {
                throw new Exception("Failed to write to log file: " . self::$logFile);
            }
        } catch (Exception $e) {
            error_log("Logging failed: " . $e->getMessage());
        }
    }
    
    public static function debug($message, $context = []) {
        if (!isset(self::$config['development_mode']) || !self::$config['development_mode']) {
            return;
        }
        self::log($message, 'debug', $context);
    }
    
    public static function info($message, $context = []) {
        self::log($message, 'info', $context);
    }
    
    public static function error($message, $context = []) {
        self::log($message, 'error', $context);
        error_log("$message " . (!empty($context) ? json_encode($context) : ''));
    }
    
    public static function warn($message, $context = []) {
        self::log($message, 'warn', $context);
    }
} 