<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * JobController
 * Handles job queue operations between PHP and the Python Neural Worker using NATS file handshakes.
 */
class JobController extends BaseController
{
    /**
     * GET /php/job/index
     * Fetch the next pending job for the Neural Worker (FIFO structure).
     */
    public function index()
    {
        $conditions = [
            'tbl'   => 'jobs',
            'where' => ['status' => 'pending'],
            'order' => ['id' => 'ASC'],
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
        // Capture incoming parameters safely
        $inputPath = $_POST['path'] ?? 'test.csv';

        // Trigger the parent centralized resolver diagnostics mode
        $paths = $this->baseUpload($inputPath);

        // Build the metadata framework layout from request context
        $meta = [
            'path' => $_POST['path'] ?? 'storage/uploads/test.csv',
            'type' => $_POST['type'] ?? 'csv'
        ];

        // Extract raw document contents safely using unified paths
        $extractedText = $this->prepareJob($meta);

        // Prepare the dataset properties for MariaDB mapping
        $jobData = [
            'status'     => 'pending',
            'file_name'  => basename($meta['path']),
            'payload'    => $extractedText ?: json_encode(array_merge($meta, ['created_by' => 'system_mock'])),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save initial job tracking metrics
        $result = $this->db->save('jobs', $jobData);

        if ($result === false) {
            $this->logger->log("NP Step 1: Failed to write initial job header to MariaDB", 'ERROR');
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to create job'
            ], 500);
        }

        $this->logger->log("NP Step 1: Job record saved to DB with ID: " . $result, 'INFO');
        $this->logger->log("NP Step 1.5: Content parsing execution status [Extracted: " . ($extractedText ? 'YES' : 'NO') . "]", 'INFO');

        // Build NATS handshake files atomically
        $this->create_nats_item((int)$result, $meta);
        
        return $this->json([
            'status'  => 'success',
            'message' => 'Job posted to the queue & NATS handshake triggered.',
            'job_id'  => $result
        ]);
    }

    /**
     * Internal Handshake: Acts as the "Publisher" for NATS-Lite.
     */
    private function create_nats_item(int $jobId, array $payload): bool
    {
        $handshake = [
            'job_id'    => $jobId,
            'timestamp' => time(),
            'action'    => 'process_new_job',
            'data'      => $payload
        ];

        // Switch to parent array structures cleanly
        $paths = $this->baseUpload();
        $directory = $paths['nats_ingest_dir'] ?: $this->location->storage('uploads/nats/ingest/');
        
        $finalPath = rtrim($directory, '/') . "/job_{$jobId}.json";
        $tempPath  = "{$finalPath}.tmp";
        
        file_put_contents($tempPath, json_encode($handshake, JSON_PRETTY_PRINT), LOCK_EX);
        
        return rename($tempPath, $finalPath);
    }

    /**
     * PUT /php/job/update/{id}
     * Persists neural chunks to MariaDB via framework repository layer.
     */
    public function update($id)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?? [];
        
        $id = (int)$id;
        $status = $data['status'] ?? 'unknown';

        $updateData = [
            'id'     => $id,
            'status' => $status
        ];

        if ($status === 'completed' || $status === 'failed') {
            $updateData['finished_at'] = date('Y-m-d H:i:s');
        }

        $this->logger->log("NP Step 4: Update received from Python Worker for Job ID: {$id}", 'INFO');

        // Update Job state metrics
        $result = $this->db->save('jobs', $updateData);

        // Sync Vectors directly to MariaDB safely (No raw SQL)
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
            $this->logger->log("NP Step 4 Failed: MariaDB update failed to write for job {$id}", 'ERROR');
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
        // Leverage our single source of truth for safe file extraction
        $filename = basename($meta['path'] ?? 'test.csv');
        $paths = $this->baseUpload($filename);
        $resolvedPath = $paths['target_file'] ?: $this->location->storage("uploads/{$filename}");

        if (!file_exists($resolvedPath) || !is_readable($resolvedPath)) {
            $this->logger->log("NP Prepare Job: Missing target source asset file at path: {$resolvedPath}", 'ERROR');
            return null; 
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->parseCsvToString($resolvedPath);
        }

        $extractedText = file_get_contents($resolvedPath);
        return !empty(trim($extractedText)) ? trim($extractedText) : null;
    }

    /**
     * Sub-method: Dedicated Robust CSV parsing driver preserving row structures.
     */
    private function parseCsvToString(string $resolvedPath): ?string
    {
        $extractedText = '';

        if (($handle = fopen($resolvedPath, 'r')) === false) {
            return null;
        }

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $escapedRow = array_map(function($cell) {
                $cell = trim((string)$cell);
                if (strpbrk($cell, ",\"\n\r") !== false) {
                    return '"' . str_replace('"', '""', $cell) . '"';
                }
                return $cell;
            }, $data);

            $extractedText .= implode(',', $escapedRow) . "\n";
        }

        fclose($handle);
        return !empty(trim($extractedText)) ? trim($extractedText) : null;
    }

    /**
     * POST /php/job/finalize/{id}
     * Called by Python worker after processing data pipelines.
     */
    public function finalize($id)
    {
        if (!$id || !is_numeric($id)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid job ID'], 400);
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?? [];
        
        // FIX: Extract vector items array from incoming payload safely
        $vectorPayloads = $data['chunks'] ?? $data['vectors'] ?? [];

        try {
            if (!empty($vectorPayloads) && is_array($vectorPayloads)) {
                foreach ($vectorPayloads as $row) {
                    $this->db->save('vectors', [
                        'job_id'    => (int)$id,
                        'content'   => $row['content'] ?? '',
                        'embedding' => json_encode($row['embedding'] ?? []),
                        'pref'      => $row['pref'] ?? null
                    ]);
                }
            }

            $this->db->save('jobs', [
                'id'          => (int)$id,
                'status'     => 'completed',
                'finished_at'=> date('Y-m-d H:i:s')
            ]);

            $this->logger->log("Job {$id} finalized and sync targets mapped out.", 'INFO');

            return $this->json([
                'status'  => 'success',
                'message' => "Job {$id} vectors saved securely."
            ]);

        } catch (\Exception $e) {
            $this->logger->log("Finalize failed for job {$id}: " . $e->getMessage(), 'ERROR');
            return $this->json([
                'status'  => 'error',
                'message' => 'Failed to ingest vectors into storage layer'
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
