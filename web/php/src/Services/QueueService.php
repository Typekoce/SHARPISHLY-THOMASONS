<?php

declare(strict_types=1);

namespace App\Services;

use Redis;
use Exception;

/**
 * QUEUE SERVICE (THOMASONS V3)
 * Orchestrates the hand-off between PHP (Producer) and Python (Consumer).
 * Uses EnvironmentService and Location for infrastructure-aware connectivity.
 */
class QueueService extends BaseService
{
    private static ?QueueService $instance = null;
    private Redis $redis;
    private string $queueName;
    private EnvironmentService $env;
    private Location $location;

    protected function __construct()
    {
        $this->env = EnvironmentService::getInstance();
        $this->location = new Location();
        
        // 1. Dynamic Redis Discovery from SSOT
        $host = $this->env->get('REDIS_HOST', 'sharpishly-redis');
        $port = (int) $this->env->get('REDIS_PORT', 6379);
        $this->queueName = $this->env->get('REDIS_QUEUE', 'neural_queue');

        $this->redis = new Redis();
        try {
            // Attempt connection with the SSOT-provided timeout
            $timeout = (int) filter_var($this->env->get('DB_START_PERIOD', '5s'), FILTER_SANITIZE_NUMBER_INT);
            
            if (!$this->redis->connect($host, $port, (float)$timeout)) {
                throw new Exception("Could not establish connection to Redis at {$host}:{$port}");
            }

            // Handle Redis Auth if defined in .env/SSOT
            $pass = $this->env->get('REDIS_PASSWORD');
            if ($pass && !$this->redis->auth($pass)) {
                throw new Exception("Redis authentication failed.");
            }
        } catch (Exception $e) {
            // Log to the physical log path defined in Location.php
            error_log("CRITICAL: QueueService Redis Failure: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Pushes a job payload into the pipeline.
     * Validates file existence via Location service before queuing.
     * * @param array $data Must include 'job_id' and 'file_path'.
     * @return bool Success status
     */
    public function push(array $data): bool
    {
        if (!isset($this->redis)) {
            return false;
        }

        try {
            // INTEGRITY CHECK: Ensure the file actually exists in the uploads folder
            if (isset($data['file_path'])) {
                $fullPath = $this->location->uploads($data['file_path']);
                if (!file_exists($fullPath)) {
                    error_log("Queue Error: Physical file missing at {$fullPath}. Aborting push.");
                    return false;
                }
            }

            // ENRICH PAYLOAD: Add system metadata for the Python consumer
            $payload = json_encode(array_merge($data, [
                'pushed_at'   => time(),
                'origin'      => 'php-backend',
                'sys_version' => $this->env->get('SYS_VERSION', '1.0.0'), // From Makefile
                'app_env'     => $this->env->isDevMode() ? 'dev' : 'prod'
            ]));

            // Atomic Push to the tail of the list
            $result = $this->redis->lPush($this->queueName, $payload);
            
            return $result !== false;
        } catch (Exception $e) {
            error_log("Queue Push Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Optional: Clear the queue (Useful for 'make clean' or 'make reset' triggers)
     */
    public function purge(): bool
    {
        return (bool) $this->redis->del($this->queueName);
    }
}