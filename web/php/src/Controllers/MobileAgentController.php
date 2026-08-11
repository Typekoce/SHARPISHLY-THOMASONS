<?php

namespace App\Controllers;

use App\Models\AgentModel;
use App\Services\PromptService;
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
        $input = json_decode($raw ?: '', true) ?? [];
        $instruction = trim((string) ($input['instruction'] ?? ''));

        if ($instruction === '') {
            $this->json(['status' => 'error', 'error' => 'Instruction cannot be empty'], 400);
            return;
        }

        try {
            $agentModel = new AgentModel();
            $prompt = new PromptService();

            // Structural parse (local)
            $content = $prompt->read($instruction);

            // RAG-enriched conditions (remote + local)
            $conditions = $prompt->promptToConditions($instruction);

            // Generate dynamic agent name from parsed content payload
            $agentName = $this->createAgentName($content);

            $inserted = $agentModel->create([
                'agent_name'  => $agentName,
                'description' => $instruction,
                'content'     => json_encode($content),
                'pref'        => json_encode($conditions),
                'status'      => 'pending',
                'created_at'  => $this->now(),
            ]);

            if (!$inserted) {
                $this->json(['status' => 'error', 'error' => 'Database insertion failed.'], 500);
                return;
            }

            $this->json(['status' => 'success', 'data' => ['id' => $inserted, 'agent_name' => $agentName]]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to create agent: ' . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create Agent name from $content array or JSON payload
     * 
     * @param array|string $content Parsed prompt output from PromptService
     * @return string
     */
    public function createAgentName($content): string
    {
        if (is_string($content)) {
            $content = json_decode($content, true) ?? [];
        }

        $action = $content['action'] ?? 'GENERIC_QUERY';
        $table  = $content['table'] ?? 'queries';

        // 1. Derive from action route (e.g., DISPATCH_SMS -> "Sharpishly Dispatch Sms Agent")
        if ($action !== 'GENERIC_QUERY') {
            $formattedAction = ucwords(strtolower(str_replace('_', ' ', $action)));
            return "Sharpishly {$formattedAction} Agent";
        }

        // 2. Derive from target table (e.g., agent_tasks -> "Sharpishly Agent Tasks Processor")
        if ($table !== 'queries') {
            $formattedTable = ucwords(strtolower(str_replace('_', ' ', $table)));
            return "Sharpishly {$formattedTable} Processor";
        }

        // 3. Fallback to NLP tokens from sentence 1 clause 1
        $firstClause = $content['payload']['sentence_1']['clause_1'] ?? [];
        if (!empty($firstClause['nlp']['tokens'])) {
            $keywords = array_slice($firstClause['nlp']['tokens'], 0, 3);
            return 'Sharpishly ' . ucwords(implode(' ', $keywords)) . ' Agent';
        }

        return 'Sharpishly General Agent';
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