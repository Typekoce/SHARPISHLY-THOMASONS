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
        $logger = new \App\Services\Logger();

        $payload = json_encode([
            'path'       => $this->location->uploads('test.csv'),
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
            $logger->error("NP Step 1: Failed to write initial job header to MariaDB");
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to create job'
            ], 500);
        }

        $logger->info("NP Step 1: Job record saved to DB", ['job_id' => $result]);

        // --- THE MISSING PIPELINE BRIDGE ---
        // We look for the file and extract its content text into the job's payload field
        // before informing the Python side.
        $prepared = $this->prepareJob((int)$result);
        
        $logger->info("NP Step 1.5: Content parsing execution", [
            'job_id'             => $result,
            'file_extracted_ok'  => $prepared
        ]);

        // --- THE NATIVE PROTOCOL TRIGGER ---
        // We drop the file inside the filesystem ingest folder to alert Python
        $payloadData = json_decode($payload, true); 
        $this->create_nats_item((int)$result, $payloadData);
        
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

        $directory = $this->location->nats('ingest');
        $finalPath = "{$directory}/job_{$jobId}.json";
        
        // --- LATERAL THINKING: ATOMIC WRITING ---
        $tempPath = "{$finalPath}.tmp";
        
        file_put_contents($tempPath, json_encode($handshake, JSON_PRETTY_PRINT), LOCK_EX);
        
        return rename($tempPath, $finalPath);
    }

    /**
     * PUT /php/job/update/{id}
     * Persists neural chunks to MariaDB using the ScaffoldModel schema.
     */
    public function update($id)
    {
        $logger = new \App\Services\Logger();
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $id = (int)$id;
        $status = $data['status'] ?? 'unknown';

        $updateData = [
            'id'     => $id,
            'status' => $status
        ];

        if ($status === 'completed' || $status === 'failed') {
            $updateData['finished_at'] = date('Y-m-d H:i:s');
        }

        $logger->info("NP Step 4: Update received from Python Worker", [
            'job_id'     => $id,
            'status'     => $status,
            'chunk_size' => isset($data['chunks']) ? count($data['chunks']) : 0
        ]);

        // 1. Update Job Status
        $result = $this->db->save('jobs', $updateData);

        // 2. Sync Vectors (Neural Path)
        if (!empty($data['chunks']) && is_array($data['chunks'])) {
            foreach ($data['chunks'] as $chunk) {
                $this->db->save('vectors', [
                    'job_id'    => $id,
                    'content'   => $chunk['content'] ?? '',
                    'embedding' => json_encode($chunk['embedding'] ?? []),
                    'pref'      => $chunk['pref'] ?? null
                ]);
            }
        }

        if ($result === false) {
            $logger->error("NP Step 4 Failed: MariaDB update failed to write", ['job_id' => $id]);
            return $this->json(['status' => 'error', 'message' => 'DB Save Failed'], 500);
        }

        return $this->json([
            'status' => 'success', 
            'job_id' => $id, 
            'chunks_synced' => count($data['chunks'] ?? [])
        ]);
    }

    /**
     * PUT /php/job/update/{id}
     * Streamlined for debugging the Python handshake.
     */
    public function mock_update($id)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $status = $data['status'] ?? 'unknown';

        $updateData = [
            'id'     => (int)$id,
            'status' => $status
        ];

        if ($status === 'completed' || $status === 'failed') {
            $updateData['finished_at'] = date('Y-m-d H:i:s');
        }

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
            $cleanContent = preg_replace('/\s+/', ' ', strip_tags($rawContent));
            $cleanContent = trim($cleanContent);

            // Update the job with the actual text data
            $this->db->save('jobs', [
                'id'      => $jobId,
                'payload' => $cleanContent, 
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
                foreach ($batch as $row) {
                    $this->db->save('vectors', $row);
                }
            }

            $this->db->save('jobs', [
                'id'          => (int)$id,
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

    /**
     * GET /php/job/payload/{id}
     * Streams the raw BLOB data from MariaDB to the requester.
     */
    public function payload($id)
    {
        $job = $this->db->find('jobs', ['id' => $id]);

        if (!$job || empty($job['payload'])) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Payload not found or empty'
            ], 404);
        }
        
        return $this->json(['status' => 'success', 'payload' => $job['payload']]);
    }
}
