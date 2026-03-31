<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\NeuralService;
use Throwable;
use Exception;

class UploadController extends BaseController
{
    /**
     * POST /php/upload
     * Handles file reception, Database entry, and AI Pipeline handover.
     */
    public function index(): void
    {
        try {
            $file = $_FILES['document'] ?? $_FILES['csv_data'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->json(['status' => 'error', 'message' => 'File upload failed or missing'], 400);
                return;
            }

            // 1. Generate a UUID for the Document (Matches our migration)
            $docId = bin2hex(random_bytes(18)); // Simple unique ID for now
            $filename = $docId . '_' . basename($file['name']);
            $targetPath = $this->loc->storage("uploads/$filename");

            // 2. Persist to shared volume
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->json(['status' => 'error', 'message' => 'Failed to move file to storage'], 500);
                return;
            }

            // 3. Create the Database Record (Source of Truth)
            $this->db->save('documents', [
                'id'       => $docId,
                'filename' => $file['name'],
                'status'   => 'pending'
            ]);

            // 4. Create the Job Tracking Record
            $jobId = $this->db->save('jobs', [
                'title'   => 'Neural Ingest: ' . $file['name'],
                'payload' => json_encode(['doc_id' => $docId, 'path' => $targetPath]),
                'status'  => 'pending'
            ]);

            // 5. Trigger the Handshake (NeuralService)
            $neural = new NeuralService();
            $handover = $neural->ingest($filename, $docId, [
                'original_name' => $file['name'],
                'job_id'        => $jobId
            ]);

            if ($handover) {
                $this->json([
                    'status'      => 'accepted',
                    'document_id' => $docId,
                    'job_id'      => $jobId,
                    'message'     => 'Document is being processed by the Neural Engine'
                ]);
            } else {
                // If AI engine is down, we still have the file and DB record for a retry
                $this->json([
                    'status'  => 'queued',
                    'job_id'  => $jobId,
                    'message' => 'File saved, but Neural Engine is currently offline.'
                ], 202);
            }

        } catch (Throwable $e) {
            $this->logger->log("Upload Error: " . $e->getMessage(), 'ERROR');
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /php/upload/status?id=...
     */
    public function status(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->json(['status' => 'error', 'message' => 'Missing Job ID'], 400);
            return;
        }

        $job = $this->db->find([
            'tbl'   => 'jobs',
            'where' => ['id' => $id],
            'limit' => 1
        ]);
        
        $this->json($job[0] ?? ['status' => 'not_found']);
    }
}