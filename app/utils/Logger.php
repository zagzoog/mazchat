<?php
class Logger {
    private static $instance = null;
    private static $config = null;
    private static $logFile = null;
    
    private function __construct() {
        self::$config = require_once __DIR__ . '/../../config.php';
        self::$logFile = __DIR__ . '/../../logs/debug.log';
        
        // Ensure config is an array
        if (!is_array(self::$config)) {
            self::$config = [
                'debug_logging' => true,
                'development_mode' => true
            ];
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function log($message, $type = 'info', $context = []) {
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
        
        try {
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Failed to write to log file: " . $e->getMessage());
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
    }
    
    public static function warn($message, $context = []) {
        self::log($message, 'warn', $context);
    }
} 