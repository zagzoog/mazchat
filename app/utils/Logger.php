<?php
class Logger {
    private static $instance = null;
    private static $config = null;
    private static $logFile = null;
    
    private function __construct() {
        self::$config = require_once __DIR__ . '/../../config.php';
        self::$logFile = __DIR__ . '/../../logs/debug.log';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function log($message, $type = 'info') {
        if (self::$instance === null) {
            self::getInstance();
        }
        
        if (!self::$config['debug_logging']) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
        
        try {
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Failed to write to log file: " . $e->getMessage());
        }
    }
    
    public static function debug($message) {
        if (self::$config['development_mode']) {
            self::log($message, 'debug');
        }
    }
    
    public static function info($message) {
        self::log($message, 'info');
    }
    
    public static function error($message) {
        self::log($message, 'error');
    }
    
    public static function warn($message) {
        self::log($message, 'warn');
    }
} 