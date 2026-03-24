<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Registry;

class FileController extends BaseController
{
    /**
     * POST /php/File/upload
     * Handles multipart/form-data from the SPA
     */
    public function upload(): void
    {
        try {
            if (empty($_FILES['file'])) {
                throw new Exception("No file part in request.");
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed with error code: " . $file['error']);
            }

            // 1. Setup paths
            $rawDir = '/var/www/html/storage/uploads/raw';
            if (!is_dir($rawDir)) {
                mkdir($rawDir, 0775, true);
            }

            $filename = basename($file['name']);
            $targetPath = $rawDir . '/' . $filename;

            // 2. Move File
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Could not move uploaded file to storage.");
            }

            // 3. Log Job in DB (DbJson or MySQL)
            // Using positional placeholders to keep it compatible with our DB wrappers
            $this->db->execute(
                "INSERT INTO jobs (type, payload, status, created_at) VALUES (?, ?, ?, ?)",
                [
                    'csv_ingest', 
                    json_encode([
                        'path' => $targetPath,
                        'original_name' => $filename
                    ]),
                    'pending',
                    date('Y-m-d H:i:s')
                ]
            );

            $this->json([
                'status' => 'success',
                'message' => 'File received and ingestion job queued.',
                'file' => $filename
            ]);

        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /php/File/status
     * Polling endpoint for the SPA to check job progress
     */
    public function status(): void
    {
        $jobs = $this->db->query("SELECT * FROM jobs ORDER BY id DESC LIMIT 5");
        $this->json($jobs);
    }
}