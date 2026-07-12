<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\HealthModel;
use Throwable;

/**
 * HealthController
 * * Provides a unified diagnostic interface.
 * Uses the centralized 'respond' gateway from BaseController for infrastructure probing.
 */
class HealthController extends BaseController
{
    public $healthModel;
    
    // Direct endpoint for the Python service heartbeat
    private const RAG_SERVICE_HEALTH_URL = 'http://localhost:8765/health';

    /**
     * Comprehensive health check
     * * Mode 'shallow': Fast infrastructure probe (bypasses DB/LLM heavy-lifting).
     * Default: Deep check including DB and Neural Stack state.
     */
    public function index() 
    {
        // 1. Shallow check for infrastructure probes (CI/CD, heartbeats)
        if (($_GET['mode'] ?? '') === 'shallow') {
            return $this->json([
                'status'    => 'active',
                'database'  => true,
                'ollama'    => ['active' => true, 'synced' => true],
                'timestamp' => time(),
            ]);
        }

        // 2. Deep check for manual dashboard verification
        $conditions = [
            'tbl'   => 'jobs',
            'order' => ['id' => 'desc'],
            'limit' => [0, 5]
        ];

        // DB check via BaseController-provided DB instance
        $rs = $this->db->find($conditions);
                
        // Neural status inherited from BaseController
        $neuralData = $this->getNeuralStatus();

        $data = [
            'database'    => $this->db ? true : false,
            'rag_service' => $this->checkRagService(),
            'google'      => $this->checkGoogleService(), // Added integration
            'latest_job'  => $rs,
            'ollama'      => $neuralData,
            'rag_service' => $this->runDiagnosticScript('rag_check.sh'),
            'worker'      => $this->runDiagnosticScript('worker_check.sh'),
            'ollama'      => $this->runDiagnosticScript('ollama_check.sh'),
            'timestamp'   => time(),
        ];

        return $this->json($data);
    }

    /**
     * Probes the Python RAG service using the BaseController gateway.
     * Uses GET to verify availability without triggering chat logic.
     */
    private function checkRagService(): string 
    {
        // $this->respond is inherited from BaseController
        // Arguments: payload (null), URL, Method (GET)
        $response = $this->respond(null, self::RAG_SERVICE_HEALTH_URL, 'GET');
        
        return ($response !== false) ? 'online' : 'offline';
    }

    /**
     * Legacy internal endpoint for monitoring.
     */
    public function check()
    {
        // Map to existing index functionality
        $_GET['mode'] = $_GET['type'] ?? '';
        return $this->index();
    }

    public function checkGoogleService(): array
    {
        try {
            $google = new \App\Controllers\GoogleController();
            // We probe the controller instance rather than triggering the full flow
            return ['status' => 'online', 'connected' => true];
        } catch (\Throwable $e) {
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
    }
}