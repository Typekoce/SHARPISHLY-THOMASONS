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
}