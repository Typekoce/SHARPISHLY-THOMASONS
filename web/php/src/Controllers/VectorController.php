<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Location;
use Exception;

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
        $vector = $this->db->find('vectors', ['status' => 'pending']);

        return $this->json($vector ?: ['message' => 'No pending vectors']);
    }

    /**
     * POST /php/vector/store
     * Receives the generated embedding and metadata from Python.
     */
    public function store()
    {
        $data = $this->getJsonInput(); 

        if (!$data || !isset($data['job_id'], $data['embedding'])) {
            return $this->json(['error' => 'Invalid vector payload'], 400);
        }

        $saveData = [
            'job_id'    => (int)$data['job_id'],
            'content'   => $data['content'] ?? '',
            'embedding' => json_encode($data['embedding']),
            'pref'      => $data['pref'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->db->insert('vectors', $saveData);

        return $this->json([
            'status' => $result ? 'success' : 'error',
            'vector_id' => $this->db->lastInsertId()
        ]);
    }

    /**
     * GET /php/vector/import/{id}
     * Task 2: Imports chunks from storage/vectors/job_{id}.json
     */
    public function import(int $id)
    {
        $path = Location::vectorStorage() . "/job_{$id}.json";

        if (!file_exists($path)) {
            return $this->json(['error' => "Import file missing: $path"], 404);
        }

        $payload = json_decode(file_get_contents($path), true);

        if (!$payload || !isset($payload['data'])) {
            return $this->json(['error' => "Malformed JSON data"], 400);
        }

        $count = 0;
        foreach ($payload['data'] as $chunk) {
            // Task 3: Save vector to DB
            $this->db->insert('vectors', [
                'job_id'    => $id,
                'content'   => $chunk['content'],
                'embedding' => json_encode($chunk['embedding']),
                'pref'      => $chunk['meta']['chunk_num'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $count++;
        }

        // Close the loop: Mark job as done
        $this->db->update('jobs', ['status' => 'completed'], ['id' => $id]);

        return $this->json([
            'status' => 'success',
            'imported_count' => $count,
            'job_id' => $id
        ]);
    }

/**
 * GET /php/vector/show/{id}
 * Fetch all vectors/chunks for a specific job.
 */
public function show(int $id)
{
    // Using your established DB pattern to get all chunks for the job
    $vectors = $this->db->findAll('vectors', ['job_id' => $id]);

    return $this->json($vectors ?: ['error' => 'No vectors found for this job']);
}
}