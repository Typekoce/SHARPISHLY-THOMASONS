<?php

namespace App\Services;

/**
 * RedisService
 * Wraps the phpredis C-extension to provide a high-speed buffer 
 * between the @tardis VM and the Docker MySQL persistence layer.
 */
class RedisService extends BaseService 
{
    private $redis = null;
    private string $host = 'sharpishly-redis';
    private int $port = 6379;

    public function __construct(Logger $logger)
    {
        // BaseService handles $this->logger and path setup
        parent::__construct();
        $this->logger = $logger;

        // Defensive check: Ensure the phpredis extension is loaded
        if (class_exists('\Redis')) {
            $this->redis = new \Redis();
        } else {
            $this->logger->error("RedisService: The PHP Redis extension is NOT installed.");
        }
    }

    /**
     * Establish connection only when needed.
     */
    public function connect(): bool
    {
        if (!$this->redis) {
            return false;
        }

        try {
            if (!$this->redis->isConnected()) {
                // Standard connection for volatile buffer data
                $this->redis->connect($this->host, $this->port);
            }
            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Redis Connection Failure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drains the buffer (used by JobController::finalize).
     */
    public function lPop(string $key)
    {
        if (!$this->connect()) return false;
        
        $data = $this->redis->lPop($key);
        return $data !== false ? $data : null;
    }

    /**
     * Pushes to the buffer (useful for PHP -> Python communication).
     */
    public function rPush(string $key, string $value): bool
    {
        if (!$this->connect()) return false;
        
        return (bool)$this->redis->rPush($key, $value);
    }

    /**
     * Clean up keys after ingestion is complete.
     */
    public function delete(string $key): bool
    {
        if (!$this->connect()) return false;
        
        return (bool)$this->redis->del($key);
    }
}