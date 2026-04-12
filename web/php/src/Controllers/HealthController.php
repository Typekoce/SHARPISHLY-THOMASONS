<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\HealthModel;
use Throwable;

class HealthController extends BaseController
{

    public $healthModel;

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
                
        // 🚀 Inherited from BaseController
        $neuralData = $this->getNeuralStatus();

        $data = [
            'status'     => ($redisAlive && $neuralData['synced']) ? 'healthy' : 'degraded',
            'database'   => true, 
            'latest_job' => $rs,
            'queue_info' => [
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
        $this->healthModel = new HealthModel();

        $dbReady    = $this->healthModel->isDatabaseReady();
        $neural     = $this->getNeuralStatus();

        $status = [
            'status'    => 'active',
            'database'  => $dbReady,
            'ollama'    => $neural['active'],
            'synced'    => $neural['synced'],
            'healthy'   => $dbReady && $neural['synced']
        ];

        return $this->json($status, $status['healthy'] ? 200 : 503);
    }
}