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
     * Create a job and extract plain text data prior to NATS queue handshake dispatching.
     */
    public function create()
    {
        $logger = new \App\Services\Logger();

        // 1. Explicitly build the metadata layout from the request context
        $meta = [
            'path' => $_POST['path'] ?? 'storage/uploads/test.csv',
            'type' => $_POST['type'] ?? 'csv'
        ];

        // 2. Extract the text content before inserting into the database
        $extractedText = $this->prepareJob($meta);

        // 3. Prepare the dataset for MariaDB
        $jobData = [
            'status'    => 'pending',
            'file_name' => basename($meta['path']),
            // DRY: Save raw text content if extracted; fallback to JSON tracking metadata
            'payload'   => $extractedText ?: json_encode(array_merge($meta, ['created_by' => 'system_mock'])),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 4. Save initial job tracking record to database
        $result = $this->db->save('jobs', $jobData);

        if ($result === false) {
            $logger->error("NP Step 1: Failed to write initial job header to MariaDB");
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to create job'
            ], 500);
        }

        $logger->info("NP Step 1: Job record saved to DB", ['job_id' => $result]);

        $logger->info("NP Step 1.5: Content parsing execution", [
            'job_id'            => $result,
            'file_extracted_ok' => !empty($extractedText)
        ]);

        // 5. Build NATS payload wrapper and trigger atomic file-system transaction
        $this->create_nats_item((int)$result, $meta);
        
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
     * Parse raw document text content prior to NATS dispatching
     */
    private function prepareJob(array $meta): ?string
    {
        if (!isset($this->location)) {
            $this->location = new \App\Services\Location();
        }

        // Isolate filename to strip out brittle host-specific home paths
        $filename = basename($meta['path'] ?? 'test.csv');
        $resolvedPath = $this->location->uploads($filename);

        if (!file_exists($resolvedPath) || !is_readable($resolvedPath)) {
            return null; // Triggers file_extracted_ok: false
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $extractedText = '';

        if ($extension === 'csv') {
            if (($handle = fopen($resolvedPath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $cleanRow = array_filter(array_map('trim', $data));
                    if (!empty($cleanRow)) {
                        $extractedText .= implode(' ', $cleanRow) . " ";
                    }
                }
                fclose($handle);
            }
        } else {
            $extractedText = file_get_contents($resolvedPath);
        }

        return !empty(trim($extractedText)) ? trim($extractedText) : null;
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
        $conditions = [
            'tbl'   => 'jobs',
            'where' => ['id' => (int)$id]
        ];

        $jobResult = $this->db->find($conditions);
        $job = $jobResult[0] ?? null; 

        if (!$job || empty($job['payload'])) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Payload not found or empty'
            ], 404);
        }
        
        return $this->json(['status' => 'success', 'payload' => $job['payload']]);
    }
}
