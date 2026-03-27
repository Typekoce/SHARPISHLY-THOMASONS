<?php
declare(strict_types=1);

namespace App\Controllers;

use Throwable;
use Exception;

class UploadController extends BaseController
{
    // NO constructor here unless you call parent::__construct();
    
    public function index(): void
    {
        try {
            // 1. We use $this->loc which was already created by BaseController
            $file = $_FILES['csv_data'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
                return;
            }

            // 2. Use the Location service properties directly
            $uploadDir = $this->loc->uploads();
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $target = $this->loc->uploads(bin2hex(random_bytes(8)) . '_' . $file['name']);

            if (move_uploaded_file($file['tmp_name'], $target)) {
                
                // 3. Use the Db service (PDO) created by BaseController
                $jobId = $this->db->save('jobs', [
                    'title'   => 'Neural Ingest: ' . $file['name'],
                    'payload' => json_encode(['path' => $target]),
                    'status'  => 'pending'
                ]);

                $this->json([
                    'status' => 'accepted',
                    'job_id' => $jobId
                ]);
            }

        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function status(string $id): void
    {
        // Polling endpoint for the SPA
        $job = $this->db->find([
            'tbl'   => 'jobs',
            'where' => ['id' => $id]
        ]);
        
        $this->json($job[0] ?? ['error' => 'not found']);
    }
}