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
        $records = [];

        try {
            $agent = new AgentModel();
            $records = $agent->all();
        } catch (Throwable $e) {
            if (isset($this->logger)) {
                $this->logger->error('Failed to fetch agents: ' . $e->getMessage());
            }
        }

        $data = [
            'id'      => $id,
            'model'   => __CLASS__,
            'action'  => __FUNCTION__,
            'time'    => $this->now(),
            'records' => $records
        ];

        $this->json($data);
    }

    /**
     * Atomically fetch and claim the next pending agent record
     */
    public function claimNextPending(): ?array
    {
        // 1. Query the database using standard $this->db abstraction
        $results = $this->db->find([
            'tbl'   => $this->table,
            'where' => ['status' => 'pending'],
            'order' => ['id' => 'ASC'],
            'limit' => 1
        ]);

        $pending = $results[0] ?? null;

        if (!$pending) {
            return null;
        }

        // 2. Atomically mark as running
        $updated = $this->update((int)$pending['id'], [
            'status'     => 'running',
            'claimed_at' => date('Y-m-d H:i:s')
        ]);

        if (!$updated) {
            return null;
        }

        $pending['status'] = 'running';
        return $pending;
    }


}