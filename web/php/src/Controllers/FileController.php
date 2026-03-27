<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\TextProcessor;

class FileController extends BaseController
{
    /**
     * POST /php/upload
     * Handles multipart/form-data from the Surveyor SPA.
     * Inherits $this->db, $this->loc, $this->logger from BaseController.
     */
    public function upload(): void
    {
        try {
            $file = $_FILES['file'] ?? throw new Exception("No file received.");
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed: Code " . $file['error']);
            }

            // 1. Prepare Storage via Inherited Location Service ($this->loc)
            $targetPath = $this->loc->uploads(basename($file['name']));
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("FileSystem Error: Could not move file to storage.");
            }

            // 2. Pre-process text using our retained Service (Semantic Cleaning)
            $processor = new TextProcessor();
            $rawContent = file_get_contents($targetPath);
            $cleanChunks = $processor->prepare($rawContent, ['source' => $file['name']]);

            // 3. Queue Job using inherited DB instance ($this->db)
            // Using a structured insert instead of raw SQL strings
            $jobId = $this->db->insert('jobs', [
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
     * Polling endpoint for the SPA.
     */
    public function status(): void
    {
        // Use abstract fetch method to keep SQL logic out of the controller
        $jobs = $this->db->select("SELECT * FROM jobs ORDER BY id DESC LIMIT 10");
        $this->json($jobs);
    }
}