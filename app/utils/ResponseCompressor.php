<?php
class ResponseCompressor {
    private static $instance = null;
    private $compressionEnabled = false;
    private $bufferStarted = false;
    
    private function __construct() {
        // Check if compression is supported
        $this->compressionEnabled = extension_loaded('zlib') && 
                                  !ini_get('zlib.output_compression') &&
                                  isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
                                  strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function start() {
        if ($this->compressionEnabled && !$this->bufferStarted) {
            // Set compression headers
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            
            // Start output buffering with gzip compression
            ob_start('ob_gzhandler');
            $this->bufferStarted = true;
        } else {
            // Start normal output buffering if compression is not enabled
            ob_start();
            $this->bufferStarted = true;
        }
    }
    
    public function end() {
        if ($this->bufferStarted) {
            // Get the current buffer contents
            $content = ob_get_clean();
            $this->bufferStarted = false;
            
            if ($content !== false) {
                // If compression is enabled and content is not already compressed
                if ($this->compressionEnabled && !$this->isGzipped($content)) {
                    $compressed = gzencode($content, 9);
                    if ($compressed !== false) {
                        echo $compressed;
                        return;
                    }
                }
                
                // If compression failed or is not enabled, send uncompressed
                echo $content;
            }
        }
    }
    
    private function isGzipped($content) {
        // Check if content is already gzipped
        return (substr($content, 0, 2) === "\x1f\x8b");
    }
    
    public function isCompressionEnabled() {
        return $this->compressionEnabled;
    }
} 