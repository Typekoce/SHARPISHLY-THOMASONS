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
    public RedisService $redis;

    public function __construct()
    {
        parent::__construct();
        $this->healthModel = new HealthModel();
        $this->redis = RedisService::getInstance();
    }

    /**
     * Comprehensive health check for SPA
     * Uses inherited getNeuralStatus() for model auditing.
     */
    public function index() 
    {
        $conditions = [
            'tbl'   => 'jobs',
            'order' => ['id' => 'desc'],
            'limit' => [0, 5]
        ];

        // DB check
        $rs = $this->db->find($conditions);
        
        $redisAlive = $this->redis->isAlive();
        
        // 🚀 Inherited from BaseController
        $neuralData = $this->getNeuralStatus();

        $data = [
            'status'     => ($redisAlive && $neuralData['synced']) ? 'healthy' : 'degraded',
            'database'   => true, 
            'latest_job' => $rs,
            'redis'      => $redisAlive, 
            'queue_info' => [
                'count' => $this->redis->getQueueLength(),
                'keys'  => $this->redis->getKeys() 
            ],
            'ollama'     => $neuralData,
            'timestamp'  => time(),
        ];

        $this->json($data);
    }

    /**
     * Legacy internal endpoint for automated monitoring.
     */
    public function check()
    {
        $dbReady    = $this->healthModel->isDatabaseReady();
        $redisReady = $this->redis->isAlive();
        $neural     = $this->getNeuralStatus();

        $status = [
            'status'    => 'active',
            'database'  => $dbReady,
            'redis'     => $redisReady,
            'ollama'    => $neural['active'],
            'synced'    => $neural['synced'],
            'healthy'   => $dbReady && $redisReady && $neural['synced']
        ];

        return $this->json($status, $status['healthy'] ? 200 : 503);
    }
}