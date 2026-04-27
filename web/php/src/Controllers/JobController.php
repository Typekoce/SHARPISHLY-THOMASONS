<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * JobController
 * Handles job queue operations between PHP and the Python Neural Worker.
 */
class JobController extends BaseController
{
    /**
     * GET /php/job/index
     * Fetch the next pending job for the Neural Worker.
     */
    public function index()
    {
        $conditions = [
            'tbl'   => 'jobs',
            'where' => ['status' => 'pending'],
            'order' => ['id' => 'ASC'],        // ASC is better for FIFO processing
            'limit' => 1
        ];

        $jobs = $this->db->find($conditions);

        return $this->json($jobs);
    }

    /**
     * POST /php/job/create
     * Create a mock/test job (useful for testing the pipeline).
     */
    public function create()
    {
        $payload = json_encode([
            'path' => $this->location->uploads('test.csv'),
            'type'       => 'csv',
            'created_by' => 'system_mock'
        ]);

        $data = [
            'payload'   => $payload,
            'status'    => 'pending',
            'file_name' => 'test.csv'
        ];

        $result = $this->db->save('jobs', $data);

        if ($result === false) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to create job'
            ], 500);
        }

        return $this->json([
            'status'  => 'success',
            'message' => 'Job posted to the queue.',
            'job_id'  => $result
        ]);
    }

    /**
     * PUT /php/job/update/{id}
     * Update job status (called by Python Neural Worker).
     */
    public function update($id)
    {
        // Read JSON payload from Python
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // 1. Validation
        if (!$id || !is_numeric($id)) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Invalid or missing job ID'
            ], 400);
        }

        $allowedStatuses = ['pending', 'processing', 'completed', 'failed'];

        if (!isset($data['status']) || !in_array($data['status'], $allowedStatuses, true)) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Invalid status value'
            ], 400);
        }

        // Optional: Capture error message from worker
        $errorMessage = $data['error_message'] ?? $data['error'] ?? null;

        try {
            $updateData = [
                'id'            => (int)$id,
                'status'        => $data['status'],
                'error_message' => $errorMessage,
            ];

            // Set finished_at when job reaches terminal state
            if (in_array($data['status'], ['completed', 'failed'], true)) {
                $updateData['finished_at'] = date('Y-m-d H:i:s');
            }

            $result = $this->db->save('jobs', $updateData);

            if ($result === false) {
                $this->logger->error("Failed to update job {$id}");
                return $this->json([
                    'status'  => 'error',
                    'message' => 'Database update failed'
                ], 500);
            }

            $this->logger->info("Job {$id} updated to status: {$data['status']}");

            return $this->json([
                'status'  => 'success',
                'message' => "Job {$id} transitioned to {$data['status']}"
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Exception updating job {$id}: " . $e->getMessage());

            return $this->json([
                'status'  => 'error',
                'message' => 'Internal server error during update'
            ], 500);
        }
    }

    /**
     * Processes the raw file, cleans it, and prepares the job for the AI worker.
     */
    public function prepareJob(int $jobId)
    {
        $job = $this->db->find(['tbl' => 'jobs', 'where' => ['id' => $jobId]])[0] ?? null;

        if (!$job || empty($job['file_name'])) {
            return false;
        }

        $filePath = $this->location->uploads($job['file_name']);

        if (file_exists($filePath)) {
            $rawContent = file_get_contents($filePath);
            
            // --- The PHP Cleaning Logic ---
            // 1. Strip tags if it's HTML/XML
            // 2. Normalize whitespace (replaces newlines/tabs with single space)
            // 3. Trim edges
            $cleanContent = preg_replace('/\s+/', ' ', strip_tags($rawContent));
            $cleanContent = trim($cleanContent);

            // Update the job with the actual text data
            $this->db->save('jobs', [
                'id'      => $jobId,
                'payload' => $cleanContent, // Now 'payload' is the text, not the filename
                'status'  => 'pending'
            ]);

            return true;
        }

        return false;
    }

    /**
     * POST /php/job/finalize/{id}
     * Called by Python worker after pushing vectors to Redis.
     */
    public function finalize($id)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$id || !is_numeric($id)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid job ID'], 400);
        }

        try {
            if (!empty($batch)) {
                // Assuming you have a batch insert method or use a loop with save()
                foreach ($batch as $row) {
                    $this->db->save('vectors', $row);
                }
            }

            // Mark job as completed
            $this->db->save('jobs', [
                'id'         => (int)$id,
                'status'     => 'completed',
                'finished_at'=> date('Y-m-d H:i:s')
            ]);

            $this->logger->info("Job {$id} finalized from Redis buffer", ['vectors_count' => count($batch)]);

            return $this->json([
                'status'  => 'success',
                'message' => "Job {$id} vectors ingested from Redis"
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Finalize failed for job {$id}: " . $e->getMessage());
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to ingest vectors from Redis'
            ], 500);
        }
    }

}