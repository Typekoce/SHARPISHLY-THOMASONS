<?php

namespace App\Services;

class RedisService {
    public static $instance = null;
    public $redis;

    public function __construct() {
        $this->redis = new \Redis();
        $host = getenv('REDIS_HOST') ?: 'redis';

        try {
            $this->redis->connect($host, 6379, 1.5);
        } catch (\RedisException $e) {
            error_log('Redis connect failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isAlive() {
        try {
            $pong = $this->redis->ping();
            return $pong === '+PONG' || $pong === 'PONG' || $pong === true || $pong === 1;
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function getKeys($pattern = '*') {
        if (!$this->isAlive()) {
            return [];
        }

        $keys = [];
        $it = null;
        while ($batch = $this->redis->scan($it, $pattern)) {
            foreach ($batch as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function getQueueLength($queue = 'jobs') {
        return $this->isAlive() ? (int) $this->redis->lLen($queue) : 0;
    }
}