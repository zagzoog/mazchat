<?php
class Logger {
    private static $logFile = __DIR__ . '/../../logs/app.log';
    private static $initialized = false;
    
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        try {
            // Create logs directory if it doesn't exist
            $logDir = dirname(self::$logFile);
            if (!file_exists($logDir)) {
                if (!mkdir($logDir, 0777, true)) {
                    throw new Exception("Failed to create logs directory: $logDir");
                }
            }
            
            // Ensure the log file exists and is writable
            if (!file_exists(self::$logFile)) {
                if (!touch(self::$logFile)) {
                    throw new Exception("Failed to create log file: " . self::$logFile);
                }
                chmod(self::$logFile, 0666);
            }
            
            if (!is_writable(self::$logFile)) {
                throw new Exception("Log file is not writable: " . self::$logFile);
            }
            
            // Set error handler
            set_error_handler([self::class, 'handleError']);
            set_exception_handler([self::class, 'handleException']);
            
            // Enable error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', self::$logFile);
            
            self::$initialized = true;
            error_log("Logger initialized successfully");
        } catch (Exception $e) {
            // If we can't log to file, try to log to PHP's error log
            error_log("Logger initialization failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function log($message, $type = 'INFO', $context = []) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$type] $message";
            
            if (!empty($context)) {
                $logMessage .= "\nContext: " . json_encode($context, JSON_PRETTY_PRINT);
            }
            
            $logMessage .= "\n" . str_repeat('-', 80) . "\n";
            
            // Try to write to our log file first
            if (self::$initialized && is_writable(self::$logFile)) {
                if (!error_log($logMessage, 3, self::$logFile)) {
                    throw new Exception("Failed to write to log file");
                }
            } else {
                // Fallback to PHP's error log
                error_log($logMessage);
            }
        } catch (Exception $e) {
            error_log("Logging failed: " . $e->getMessage());
            error_log($message); // Try to log at least the original message
        }
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $message = "Error [$errno]: $errstr in $errfile on line $errline";
        self::log($message, 'ERROR');
        return false;
    }
    
    public static function handleException($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage() . 
                  "\nFile: " . $exception->getFile() . 
                  "\nLine: " . $exception->getLine() . 
                  "\nStack trace:\n" . $exception->getTraceAsString();
        self::log($message, 'EXCEPTION');
    }
} 