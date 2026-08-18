<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentModel;
use App\Services\PromptService;
use Throwable;

class MobileAgentController extends BaseController
{
    /**
     * GET /php/mobile-agent
     */
    public function index($id = ''): void
    {
        try {
            $agentModel = new AgentModel();
            $rawRecords = $agentModel->all();

            // Map database columns to the frontend schema expected by MobileController.js
            $records = array_map(static function (array $item): array {
                $content = !empty($item['content']) ? json_decode((string)$item['content'], true) : null;
                $pref    = !empty($item['pref']) ? json_decode((string)$item['pref'], true) : null;

                $item['parsed'] = $content ?? $pref;
                $item['output'] = $content ?? $pref;
                return $item;
            }, $rawRecords);

            $this->json([
                'id'      => $id,
                'model'   => __CLASS__,
                'action'  => __FUNCTION__,
                'time'    => $this->now(),
                'records' => $records
            ]);
        } catch (Throwable $e) {
            $this->logger->error("MobileAgentController index error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Failed to retrieve agent records.', 'records' => []], 500);
        }
    }

    /**
     * POST /php/mobile-agent-create
     */
    public function create(): void
    {
        $instruction = trim((string)$this->request('instruction'));

        if (empty($instruction)) {
            $this->json(['status' => 'error', 'error' => 'Instruction payload cannot be empty.'], 400);
            return;
        }

        try {
            $agentModel = new AgentModel();
            $prompt     = new PromptService();

            $content    = $prompt->read($instruction);
            $conditions = $prompt->promptToConditions($instruction);
            $agentName  = $this->createAgentName($content);

            $inserted = $agentModel->create([
                'agent_name'  => $agentName,
                'description' => $instruction,
                'content'     => json_encode($content),
                'pref'        => json_encode($conditions),
                'status'      => 'pending',
                'created_at'  => $this->timestamp(),
            ]);

            if (!$inserted) {
                $this->json(['status' => 'error', 'error' => 'Database insertion failed.'], 500);
                return;
            }

            $this->json(['status' => 'success', 'data' => ['id' => $inserted, 'agent_name' => $agentName]]);
        } catch (Throwable $e) {
            $this->logger->error("MobileAgentController create error: " . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Derive Agent name from parsed PromptService content payload
     */
    public function createAgentName($content): string
    {
        if (is_string($content)) {
            $content = json_decode($content, true) ?? [];
        }

        $action = $content['action'] ?? 'GENERIC_QUERY';
        $table  = $content['table'] ?? 'queries';

        if ($action !== 'GENERIC_QUERY') {
            return "Sharpishly " . ucwords(strtolower(str_replace('_', ' ', $action))) . " Agent";
        }

        if ($table !== 'queries') {
            return "Sharpishly " . ucwords(strtolower(str_replace('_', ' ', $table))) . " Processor";
        }

        $firstClause = $content['payload']['sentence_1']['clause_1'] ?? [];
        if (!empty($firstClause['nlp']['tokens'])) {
            $keywords = array_slice($firstClause['nlp']['tokens'], 0, 3);
            return 'Sharpishly ' . ucwords(implode(' ', $keywords)) . ' Agent';
        }

        return 'Sharpishly General Agent';
    }

    /**
     * Claim next pending agent task
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