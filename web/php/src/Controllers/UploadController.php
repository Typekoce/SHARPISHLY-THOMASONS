<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Registry;
use Exception;
use Throwable;

class UploadController extends BaseController
{
    public function index(): void
    {
        try {
            // 1. Explicitly check for the key used in your JS (csv_data)
            $file = $_FILES['csv_data'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                // This matches the error you just received
                $this->json([
                    'status'  => 'error', 
                    'message' => 'No file received in the request. Check php.ini upload_max_filesize.'
                ], 400);
                return;
            }

            // 2. Process Storage
            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $newName = bin2hex(random_bytes(8)) . '_' . basename($file['name']);
            $target  = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $db = Registry::get('db');
                
                // 3. Create the job record for the Neural Pipeline
                $jobId = $db->save('jobs', [
                    'title'   => 'Neural Ingest: ' . $file['name'],
                    'payload' => json_encode([
                        'path' => $target,
                        'type' => 'csv',
                        'size' => $file['size']
                    ]),
                    'status'  => 'pending'
                ]);

                // 4. Return the 'accepted' status the SPA is polling for
                $this->json([
                    'status' => 'accepted',
                    'job_id' => $jobId,
                    'message' => 'File received and queued.'
                ]);
            } else {
                throw new Exception("Failed to move uploaded file to storage.");
            }

        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}