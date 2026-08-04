<?php

namespace App\Controllers;

use App\Models\AgentModel;
use Throwable;

class MobileAgentController extends BaseController 
{
    /**
     * Display all agents
     */
    public function index($id = ''): void
    {
        $agent = new AgentModel();

        $this->json([
            'id'      => $id,
            'model'   => __CLASS__,
            'action'  => __FUNCTION__,
            'time'    => $this->now(),
            'records' => $agent->all()
        ]);
    }

    /**
     * Handle POST payload to generate and dispatch an agent plan
     */
    public function create(): void
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
        $instruction = trim((string) ($input['instruction'] ?? ''));

        if (empty($instruction)) {
            $this->json(['status' => 'error', 'error' => 'Instruction cannot be empty'], 400);
        }

        try {
            $agentModel = new AgentModel();
            
            $inserted = $agentModel->create([
                'agent_name'  => 'Sharpishly Agent',
                'description' => $instruction,
                'status'      => 'pending',
                'created_at'  => $this->now()
            ]);

            if ($inserted) {
                $this->json(['status' => 'success', 'data' => ['id' => $inserted]]);
            }

            $this->json(['status' => 'error', 'error' => 'Database insertion failed.'], 500);

        } catch (Throwable $e) {
            $this->logger->error('Failed to create agent: ' . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Atomically fetch and claim the next pending agent record
     */
    public function claimNextPending(): ?array
    {
        try {
            $agentModel = new AgentModel();
            return $agentModel->claimNextPending();
        } catch (Throwable $e) {
            $this->logger->error('Failed to claim pending agent: ' . $e->getMessage());
            return null;
        }
    }
}