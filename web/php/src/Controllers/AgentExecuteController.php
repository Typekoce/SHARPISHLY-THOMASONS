<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Orm;
use Throwable;

class AgentExecuteController extends BaseController 
{
    /**
     * Endpoint: /agent-execute/start
     */
    public function start(): void
    {
        // 1. Unified input retrieval (JSON body, POST, or query params)
        $jobId   = (int) ($this->request('job_id') ?? 0);
        $payload = $this->request('payload');

        $agentId    = 0;
        $conditions = [];

        // 2. Resolve payload data
        if ($jobId > 0) {
            $jobs = $this->db->find([
                'tbl'   => 'jobs',
                'where' => ['id' => $jobId],
                'limit' => 1
            ]);

            if (empty($jobs)) {
                $this->json(['error' => "Job ID {$jobId} not found"], 404);
                return;
            }

            $payloadData = json_decode($jobs[0]['payload'] ?? '[]', true);
            if (!is_array($payloadData)) {
                $payloadData = [];
            }

            // Mark job as processing
            $this->db->save('jobs', [
                'id'     => $jobId,
                'status' => 'processing'
            ]);

            $agentId    = $payloadData['agent_id'] ?? 0;
            $conditions = $payloadData['conditions'] ?? [];

        } elseif (!empty($payload)) {
            $payloadData = is_array($payload) ? $payload : json_decode($payload, true);
            if (!is_array($payloadData)) {
                $payloadData = [];
            }

            $agentId    = $payloadData['agent_id'] ?? 0;
            $conditions = $payloadData['conditions'] ?? [];
        }

        if (empty($conditions)) {
            $this->json(['error' => 'Invalid or missing conditions payload'], 400);
            return;
        }

        try {
            // 3. Dispatch execution via Orm router service
            $orm      = new Orm();
            $response = $orm->execute($conditions);
        } catch (Throwable $e) {
            $this->logger->error('Agent execution failed: ' . $e->getMessage());
            $response = ['error' => $e->getMessage()];
        }

        // 4. Persist results & log outcome
        $status = isset($response['error']) ? 'failed' : 'completed';

        $this->db->save('queries', [
            'agent_id'   => $agentId,
            'query'      => json_encode($conditions, JSON_UNESCAPED_SLASHES),
            'response'   => json_encode($response, JSON_UNESCAPED_SLASHES),
            'created_at' => $this->timestamp()
        ]);

        if ($jobId > 0) {
            $this->db->save('jobs', [
                'id'           => $jobId,
                'status'       => $status,
                'processed_at' => $this->timestamp()
            ]);
        }

        // 5. Respond using BaseController JSON helper
        $this->json([
            'status'   => $status,
            'agent_id' => $agentId,
            'result'   => $response
        ], $status === 'completed' ? 200 : 500);
    }
}