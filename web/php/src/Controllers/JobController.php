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

        // --- THE NATIVE PROTOCOL TRIGGER ---
        // We drop the 001_jobs.json to alert Python
        // Decoded payload for the handshake array
        $payloadData = json_decode($payload, true); 
        $this->create_nats_item($result, $payloadData);
        
        return $this->json([
            'status'  => 'success',
            'message' => 'Job posted to the queue & and NATS handshake triggered.',
            'job_id'  => $result
        ]);
    }

    /**
     * Internal Handshake: Acts as the "Publisher" for NATS-Lite.
     */
    private function create_nats_item(int $jobId, array $payload)
    {
        $handshake = [
            'job_id'    => $jobId,
            'timestamp' => time(),
            'action'    => 'process_new_job',
            'data'      => $payload
        ];

        // 1. Point to the 'ingest' channel folder
        // 2. Use a unique ID so we don't overwrite the queue
        $directory = $this->location->nats('ingest');
        $finalPath = "{$directory}/job_{$jobId}.json";
        
        // --- LATERAL THINKING: ATOMIC WRITING ---
        // Instead of writing directly to the queue, we write a temp file 
        // then rename it. Rename is an atomic operation in Linux.
        $tempPath = "{$finalPath}.tmp";
        
        file_put_contents($tempPath, json_encode($handshake, JSON_PRETTY_PRINT), LOCK_EX);
        
        // The moment this rename happens, the "Event" is published.
        return rename($tempPath, $finalPath);
    }

    /**
     * PUT /php/job/update/{id}
     * Streamlined for debugging the Python handshake.
     */
    public function update($id)
    {
        // Read the raw input from Python's 'requests.put'
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $status = $data['status'] ?? 'unknown';

        // Build the update array
        $updateData = [
            'id'     => (int)$id,
            'status' => $status
        ];

        // Minimalist terminal state handling
        if ($status === 'completed' || $status === 'failed') {
            $updateData['finished_at'] = date('Y-m-d H:i:s');
        }

        // Save to MariaDB
        $result = $this->db->save('jobs', $updateData);

        if ($result === false) {
            return $this->json(['status' => 'error', 'message' => 'DB Save Failed'], 500);
        }

        return $this->json([
            'status' => 'success', 
            'job_id' => $id, 
            'new_status' => $status
        ]);
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