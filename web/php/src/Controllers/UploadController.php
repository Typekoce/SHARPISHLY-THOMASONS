<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * UPLOAD CONTROLLER
 * Handles multi-selection file uploads for the Neural Pipeline.
 * Inherits $this->db, $this->loc, and $this->logger from BaseController.
 */
class UploadController extends BaseController
{
    public function index(): void
    {
        // Log the entry point for debugging/audit
        $this->logger->info("Neural Pipeline: Incoming POST", [
            'files' => array_keys($_FILES),
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        try {
            // 1. Identify the file from the SPA 'csv_data' key
            $file = $_FILES['csv_data'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->json([
                    'status'  => 'error', 
                    'message' => 'Upload failed or file missing.'
                ], 400);
                return;
            }

            // 2. Resolve target using Location service ($this->loc)
            $uploadDir = $this->loc->uploads();
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $newName = bin2hex(random_bytes(8)) . '_' . basename($file['name']);
            $target  = $this->loc->uploads($newName);

            // 3. Move and Record
            if (move_uploaded_file($file['tmp_name'], $target)) {
                
                // Save job using inherited DB abstraction
                $jobId = $this->db->save('jobs', [
                    'title'   => 'Neural Ingest: ' . $file['name'],
                    'payload' => json_encode([
                        'path' => $target,
                        'type' => 'csv',
                        'size' => $file['size']
                    ]),
                    'status'  => 'pending'
                ]);

                $this->json([
                    'status'  => 'accepted',
                    'job_id'  => $jobId,
                    'message' => 'File received and queued.'
                ]);
            } else {
                throw new Exception("FileSystem error: Could not move file to storage.");
            }

        } catch (Throwable $e) {
            $this->logger->error("Upload Process Error", ['msg' => $e->getMessage()]);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}