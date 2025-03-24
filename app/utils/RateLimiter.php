<?php

class RateLimiter {
    private $redis;
    private $prefix = 'rate_limit:';
    
    public function __construct() {
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->setOption(Redis::OPT_PREFIX, $this->prefix);
        } catch (Exception $e) {
            error_log('Redis connection failed in RateLimiter: ' . $e->getMessage());
            $this->redis = null;
        }
    }
    
    public function checkLimit($userId, $endpoint, $limit = 100, $period = 60) {
        if (!$this->redis) {
            return true; // If Redis is not available, allow the request
        }
        
        $key = "{$userId}:{$endpoint}";
        $current = $this->redis->get($key);
        
        if ($current === false) {
            // First request in the period
            $this->redis->setex($key, $period, 1);
            return true;
        }
        
        if ($current >= $limit) {
            return false;
        }
        
        $this->redis->incr($key);
        return true;
    }
    
    public function getRemainingRequests($userId, $endpoint, $limit = 100, $period = 60) {
        if (!$this->redis) {
            return $limit;
        }
        
        $key = "{$userId}:{$endpoint}";
        $current = $this->redis->get($key);
        
        if ($current === false) {
            return $limit;
        }
        
        return max(0, $limit - $current);
    }
    
    public function resetLimit($userId, $endpoint) {
        if (!$this->redis) {
            return false;
        }
        
        $key = "{$userId}:{$endpoint}";
        return $this->redis->del($key);
    }
} 