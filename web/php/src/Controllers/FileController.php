<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Core\Registry;
use App\Services\Location;

class FileController extends BaseController
{
    private Location $location;

    public function __construct()
    {
        parent::__construct();
        // Resolve the centralized location service
        $this->location = Registry::make(Location::class);
    }

    /**
     * POST /php/upload
     * Handles multipart/form-data from the Surveyor SPA
     */
    public function upload(): void
    {
        try {
            if (empty($_FILES['file'])) {
                throw new Exception("No file received in the request.");
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed with PHP error code: " . $file['error']);
            }

            // 1. Setup paths via Location Service
            $rawDir = $this->location->uploads(); 
            
            if (!is_dir($rawDir)) {
                // Use 0777 to ensure Docker container and Host stay in sync
                if (!mkdir($rawDir, 0777, true)) {
                    throw new Exception("Server Error: Cannot create upload directory.");
                }
            }

            $filename = basename($file['name']);
            $targetPath = $this->location->uploads($filename);

            // 2. Move File to /storage/uploads/raw
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("FileSystem Error: Could not move file to storage.");
            }

            // 3. Queue Job for the Worker
            // We store the path relative to the storage root to keep DB clean
            $relativeResultPath = $this->location->relative($targetPath);

            $this->db->execute(
                "INSERT INTO jobs (type, payload, status, created_at) VALUES (?, ?, ?, ?)",
                [
                    'csv_ingest', 
                    json_encode([
                        'path' => $targetPath, // Full path for the worker
                        'original_name' => $filename,
                        'relative_path' => $relativeResultPath
                    ]),
                    'pending',
                    date('Y-m-d H:i:s')
                ]
            );

            $this->json([
                'status' => 'success',
                'message' => 'Report received. Neural ingestion queued.',
                'file' => $filename,
                'job_id' => $this->db->lastInsertId()
            ]);

        } catch (Exception $e) {
            error_log("Upload Error: " . $e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /php/status
     * Polling endpoint for the SPA to check if cladding analysis is done
     */
    public function status(): void
    {
        // Fetch recent jobs so the surveyor sees the queue moving
        $jobs = $this->db->query("SELECT * FROM jobs ORDER BY id DESC LIMIT 10");
        $this->json($jobs);
    }
}