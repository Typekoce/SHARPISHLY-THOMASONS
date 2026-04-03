<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\HealthModel;
use App\Services\OllamaService;
use App\Services\RedisService;
use Throwable;

class HealthController extends BaseController
{
    private HealthModel $healthModel;
    public OllamaService $ollama;
    public RedisService $redis;

    public function __construct()
    {
        parent::__construct();
        // Instantiate the model for DB checks
        $this->healthModel = new HealthModel();
        $this->ollama = new OllamaService();
        // Use the Singleton to ensure we share the same connection resource
        $this->redis = RedisService::getInstance();
    }

    /**
     * Comprehensive health check for SPA
     * Returns flattened JSON for the Neural Handshake dashboard
     */
    public function index() {
        $conditions = [
            'tbl'   => 'jobs',
            'order' => ['id' => 'desc'],
            'limit' => [0, 1]
        ];

        $rs = $this->db->find($conditions);
        
        // Capture specific service states
        $redisAlive = $this->redis->isAlive();
        $ollamaStatus = $this->ollama->getStatus();

        // Prepare response payload
        $data = [
            'status'     => ($redisAlive && $ollamaStatus['active']) ? 'healthy' : 'degraded',
            'database'   => true, // DB connection verified by successfully calling $this->db->find
            'latest_job' => $rs,
            'redis'      => $redisAlive, // Flat boolean for SPA badge
            'queue_info' => [
                'count' => $this->redis->getQueueLength(),
                'keys'  => $this->redis->getKeys() // Live key visibility
            ],
            'ollama'     => $ollamaStatus,
            'timestamp'  => time(),
        ];

        $this->json($data);
    }

    /**
     * Interrogates services and notifies observers of health status.
     * Legacy internal endpoint
     */
    public function check()
    {
        $dbReady = $this->healthModel->isDatabaseReady();
        $redisReady = $this->redis->isAlive();
        $ollamaReady = $this->checkOllama();

        $status = [
            'status'    => 'active',
            'database'  => $dbReady,
            'redis'     => $redisReady,
            'ollama'    => $ollamaReady,
            'timestamp' => time(),
            'healthy'   => false
        ];

        // Core business health logic
        $status['healthy'] = $dbReady && $redisReady && $ollamaReady;

        if ($status['healthy']) {
            $this->logger->info("Infrastructure Healthy: Observer notification sent.");
        } else {
            $this->logger->warning("Health Check: System Degraded", $status);
        }

        return $this->json($status, $status['healthy'] ? 200 : 503);
    }

    /**
     * Check Ollama via API Handshake
     */
    private function checkOllama(): bool
    {
        try {
            $ch = curl_init('http://llm:11434/api/tags');
            // Using standard curl_setopts array for cleaner syntax
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 1
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $code === 200 && $response !== false;
        } catch (Throwable $e) {
            return false;
        }
    }
}