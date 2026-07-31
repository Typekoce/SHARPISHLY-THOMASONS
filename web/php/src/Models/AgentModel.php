<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentModel;
use App\Services\Logger;

class MobileAgentController extends BaseController 
{
    private AgentModel $agentModel;
    private Logger $logger;

    public function __construct() 
    {
        $this->agentModel = new AgentModel();
        $this->logger = $GLOBALS['logger'] ?? new Logger();
    }

    /**
     * READ: Display all agents or a specific agent by ID
     * Route: GET /mobile-agent or /mobile-agent/{id}
     */
    public function index($id = null): void 
    {
        if ($id !== null) {
            $agent = $this->agentModel->find((int)$id);
            if (!$agent) {
                $this->json(['error' => 'Agent not found'], 404);
                return;
            }
            $this->json(['status' => 'success', 'data' => $agent]);
            return;
        }

        $agents = $this->agentModel->all();
        $this->json([
            'status' => 'success',
            'data'   => $agents
        ]);
    }

    /**
     * CREATE: Store a newly generated Agent from mobile_controller.js
     * Route: POST /mobile-agent/store
     */
    public function store(): void 
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title    = trim($input['title'] ?? '');
        $category = trim($input['category'] ?? '');
        $summary  = trim($input['summary'] ?? '');

        if (empty($title) || empty($summary)) {
            $this->json(['error' => 'Title and summary are required'], 400);
            return;
        }

        $agentData = [
            'title'    => $title,
            'category' => $category ?: 'career',
            'summary'  => $summary,
            'status'   => 'dispatched'
        ];

        $newId = $this->agentModel->create($agentData);

        if (!$newId) {
            $this->logger->error("Failed to create mobile agent", $agentData);
            $this->json(['error' => 'Failed to create agent'], 500);
            return;
        }

        $agentData['id'] = $newId;

        $this->logger->info("New mobile agent created", ['id' => $newId]);
        $this->json([
            'status'  => 'success',
            'message' => 'Agent successfully dispatched',
            'data'    => $agentData
        ], 201);
    }

    /**
     * UPDATE: Update an existing Agent's status or summary
     * Route: POST/PUT /mobile-agent/update/{id}
     */
    public function update($id = null): void 
    {
        if (!$id) {
            $this->json(['error' => 'Agent ID required'], 400);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $existing = $this->agentModel->find((int)$id);
        if (!$existing) {
            $this->json(['error' => 'Agent not found'], 404);
            return;
        }

        $updateData = [];
        if (isset($input['title']))    $updateData['title']    = trim($input['title']);
        if (isset($input['category'])) $updateData['category'] = trim($input['category']);
        if (isset($input['summary']))  $updateData['summary']  = trim($input['summary']);
        if (isset($input['status']))   $updateData['status']   = trim($input['status']);

        if (empty($updateData)) {
            $this->json(['error' => 'No valid fields provided for update'], 400);
            return;
        }

        $success = $this->agentModel->update((int)$id, $updateData);

        if (!$success) {
            $this->json(['error' => 'Failed to update agent'], 500);
            return;
        }

        $this->json([
            'status'  => 'success',
            'message' => "Agent #{$id} updated successfully"
        ]);
    }

    /**
     * DELETE: Remove an agent by ID
     * Route: POST/DELETE /mobile-agent/delete/{id}
     */
    public function delete($id = null): void 
    {
        if (!$id) {
            $this->json(['error' => 'Agent ID required'], 400);
            return;
        }

        $existing = $this->agentModel->find((int)$id);
        if (!$existing) {
            $this->json(['error' => 'Agent not found'], 404);
            return;
        }

        $success = $this->agentModel->delete((int)$id);

        if (!$success) {
            $this->json(['error' => 'Failed to delete agent'], 500);
            return;
        }

        $this->logger->info("Mobile agent deleted", ['id' => $id]);
        $this->json([
            'status'  => 'success',
            'message' => "Agent #{$id} deleted successfully"
        ]);
    }
}