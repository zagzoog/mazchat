<?php

class Cache {
    private $redis;
    private $prefix = 'chat_app:';
    
    public function __construct() {
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->setOption(Redis::OPT_PREFIX, $this->prefix);
        } catch (Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
            $this->redis = null;
        }
    }
    
    public function get($key) {
        if (!$this->redis) {
            return false;
        }
        
        try {
            $value = $this->redis->get($key);
            return $value ? json_decode($value, true) : false;
        } catch (Exception $e) {
            error_log('Redis get error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function set($key, $value, $ttl = 300) {
        if (!$this->redis) {
            return false;
        }
        
        try {
            return $this->redis->setex($key, $ttl, json_encode($value));
        } catch (Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($key) {
        if (!$this->redis) {
            return false;
        }
        
        try {
            return $this->redis->del($key);
        } catch (Exception $e) {
            error_log('Redis delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function clear() {
        if (!$this->redis) {
            return false;
        }
        
        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            error_log('Redis clear error: ' . $e->getMessage());
            return false;
        }
    }
} 