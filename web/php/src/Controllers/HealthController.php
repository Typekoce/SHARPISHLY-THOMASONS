<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\HealthModel;
use Throwable;

class HealthController extends BaseController
{
    private HealthModel $healthModel;

    public function __construct()
    {
        parent::__construct();                    // Required by BaseController
        $this->healthModel = new HealthModel();
    }

    /**
     * Main health check endpoint
     * Returns detailed status of critical services
     */
    public function check()
    {
        $status = [
            'status'    => 'ok',
            'database'  => $this->healthModel->isDatabaseReady(),
            'redis'     => $this->checkRedis(),
            'ollama'    => $this->checkOllama(),
            'timestamp' => time(),
            'healthy'   => false
        ];

        // Overall health = DB + Redis (Ollama is optional for core operation)
        $status['healthy'] = $status['database'] && $status['redis'];

        if ($status['healthy']) {
            $this->logger->info("Infrastructure is fully healthy. Neural pipeline ready.");
        } else {
            $this->logger->warning("Health check degraded", [
                'database' => $status['database'],
                'redis'    => $status['redis'],
                'ollama'   => $status['ollama']
            ]);
        }

        // Return 200 if healthy, 503 Service Unavailable if degraded
        return $this->json($status, $status['healthy'] ? 200 : 503);
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            $redis = new \Redis();
            // Connect using Docker service name with short timeout
            $redis->connect('sharpishly-redis', 6379, 2);
            return $redis->ping() === '+PONG';
        } catch (Throwable $e) {
            $this->logger->error("Redis health check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check Ollama availability
     */
    private function checkOllama(): bool
    {
        try {
            $ch = curl_init('http://sharpishly-ollama:11434/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200 && $response !== false;
        } catch (Throwable $e) {
            $this->logger->error("Ollama health check failed: " . $e->getMessage());
            return false;
        }
    }
}