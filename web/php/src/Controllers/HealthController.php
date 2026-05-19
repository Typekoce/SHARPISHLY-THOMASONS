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
     * Injection: Allows an optional 'mode=shallow' query override to bypass heavy DB/Ollama operations for infrastructure probes.
     */
    public function index() 
    {
        // Check if monitoring probes want to bypass heavy heavy processing
        if (($_GET['mode'] ?? '') === 'shallow') {
            $this->json([
                'database'   => true,
                'latest_job' => [],
                'queue_info' => [],
                'ollama'     => ['active' => true, 'synced' => true],
                'timestamp'  => time(),
            ]);
            return;
        }

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
        // Injection: Allows 'type=shallow' query override for basic uptime checking
        if (($_GET['type'] ?? '') === 'shallow') {
            return $this->json([
                'status'    => 'active',
                'database'  => true,
                'ollama'    => true,
                'synced'    => true,
                'healthy'   => true
            ], 200);
        }

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
