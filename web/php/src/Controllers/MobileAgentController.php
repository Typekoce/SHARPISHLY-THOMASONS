<?php

namespace App\Controllers;

use App\Services\Db;
use App\Services\PromptService;
use Throwable;

class MobileAgentController extends BaseController 
{
    /**
     * Display all agents
     */
    public function index($id = ''): void
    {
        $db = new Db([]);
        $records = $db->find(['tbl' => 'agents']);

        $this->json([
            'id'      => $id,
            'model'   => __CLASS__,
            'action'  => __FUNCTION__,
            'time'    => $this->now(),
            'records' => $records
        ]);
    }

    /**
     * Handle POST payload to parse prompt semantics and persist directly via Db.
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
            $db = new Db([]);
            $promptService = new PromptService();

            $insertedId = $promptService->convertAndSave($instruction, $db);

            if ($insertedId) {
                $this->json([
                    'status' => 'success',
                    'data'   => [
                        'id'     => $insertedId,
                        'parsed' => $promptService->read($instruction)
                    ]
                ]);
            }

            $this->json(['status' => 'error', 'error' => 'Database insertion failed.'], 500);

        } catch (Throwable $e) {
            $this->logger->error('Failed to create agent prompt: ' . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch pending agent records
     */
    public function claimNextPending(): ?array
    {
        try {
            $db = new Db([]);
            $records = $db->find([
                'tbl'   => 'agents',
                'where' => ['status' => 'pending'],
                'limit' => 1
            ]);
            return $records[0] ?? null;
        } catch (Throwable $e) {
            $this->logger->error('Failed to claim pending agent: ' . $e->getMessage());
            return null;
        }
    }
}