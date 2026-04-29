<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Handles the communication between the Neural Engine and MariaDB vectors.
 */
class VectorController extends BaseController
{
    /**
     * GET /php/vector/index
     * Fetch the next pending vector for the Neural Worker.
     */
    public function index()
    {
        // Using your established DB find pattern
        $vector = $this->db->find('vectors', ['status' => 'pending']);

        return $this->json($vector ?: ['message' => 'No pending vectors']);
    }

    /**
     * POST /php/vector/store
     * Receives the generated embedding and metadata from Python.
     */
    public function store()
    {
        $data = $this->getJsonInput(); // Assuming BaseController helper for file_get_contents('php://input')

        if (!$data || !isset($data['job_id'], $data['embedding'])) {
            return $this->json(['error' => 'Invalid vector payload'], 400);
        }

        $saveData = [
            'job_id'    => (int)$data['job_id'],
            'content'   => $data['content'] ?? '',
            'embedding' => json_encode($data['embedding']), // Store as JSON string for MariaDB
            'pref'      => $data['pref'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->db->insert('vectors', $saveData);

        return $this->json([
            'status' => $result ? 'success' : 'error',
            'vector_id' => $this->db->lastInsertId()
        ]);
    }
}