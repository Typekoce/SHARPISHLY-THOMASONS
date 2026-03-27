<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\TextProcessor;

class FileController extends BaseController
{
    /**
     * POST /php/upload
     * Orchestrates file reception and job queuing.
     */
    public function upload(): void
    {
        try {
            $file = $_FILES['file'] ?? throw new Exception("No file received.");
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed: Code " . $file['error']);
            }

            // 1. Storage via Location Service ($this->loc)
            $targetPath = $this->loc->uploads(basename($file['name']));
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("FileSystem Error: Could not move file to storage.");
            }

            // 2. Pre-process text (Semantic Cleaning)
            $processor = new TextProcessor();
            $rawContent = file_get_contents($targetPath);
            $cleanChunks = $processor->prepare($rawContent, ['source' => $file['name']]);

            // 3. Queue Job using existing save() method
            // save() handles the INSERT and returns the lastInsertId
            $jobId = $this->db->save('jobs', [
                'type'       => 'neural_ingest',
                'status'     => 'pending',
                'payload'    => json_encode([
                    'path'          => $targetPath,
                    'original_name' => $file['name'],
                    'preview'       => $cleanChunks[0] ?? 'No readable text'
                ]),
                'steps_json' => json_encode([['t' => date('H:i:s'), 'm' => 'File received and cleaned']])
            ]);

            $this->json([
                'status' => 'success',
                'job_id' => $jobId,
                'file'   => $file['name']
            ]);

        } catch (Exception $e) {
            $this->logger->error("Upload Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /php/status
     * Polling endpoint using existing find() method.
     */
    public function status(): void
    {
        // Using your structured find() pattern
        $jobs = $this->db->find([
            'tbl'   => 'jobs',
            'order' => ['id' => 'DESC'],
            'limit' => 10
        ]);

        $this->json($jobs);
    }
}