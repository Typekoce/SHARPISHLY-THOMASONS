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
        parent::__construct();
        // We instantiate the model, but the model won't touch the DB until isDatabaseReady()
        $this->healthModel = new HealthModel();
    }

    /**
     * Interrogates services and notifies observers of health status.
     */
    public function check()
    {
        $status = [
            'status'    => 'active',
            'database'  => $this->healthModel->isDatabaseReady(),
            'redis'     => $this->checkRedis(),
            'ollama'    => $this->checkOllama(),
            'timestamp' => time(),
            'healthy'   => false
        ];

        // Core business health logic
        $status['healthy'] = $status['database'] && $status['redis'];

        if ($status['healthy']) {
            $this->logger->info("Infrastructure Healthy: Observer notification sent.");
            // THE OBSERVER: If this were a real event, we'd trigger the Migration Observer here
            // $this->notifyObservers('infrastructure_ready');
        } else {
            $this->logger->warning("Health Check: System Degraded", $status);
        }

        return $this->json($status, $status['healthy'] ? 200 : 503);
    }

    /**
     * Check Redis via OO Extension
     */
    private function checkRedis(): bool
    {
        try {
            $redis = new \Redis();
            // 1s timeout to keep the health check snappy
            if (@$redis->connect('redis', 6379, 1)) {
                return $redis->ping() === '+PONG';
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check Ollama via API Handshake
     */
    private function checkOllama(): bool
    {
        try {
            $ch = curl_init('http://llm:11434/api/tags');
            curl_setopt_all($ch, [
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