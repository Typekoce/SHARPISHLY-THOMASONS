<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Services\Location;

class FileController extends BaseController {
    public function upload(): void {
        try {
            $file = $_FILES['csv_data'] ?? throw new Exception("No file uploaded.");
            
            // Native PHP Sanitization
            $cleanContent = preg_replace('/[\x00-\x1F\x7F]/u', '', file_get_contents($file['tmp_name']));
            
            $filename = bin2hex(random_bytes(8)) . "_" . $file['name'];
            $targetPath = $this->location->uploads($filename);
            file_put_contents($targetPath, $cleanContent);

            // Set current_step to 'upload' for SPA visual feedback
            $jobId = $this->db->insert('jobs', [
                'type' => 'neural_ingest',
                'payload' => json_encode(['path' => $targetPath, 'original_name' => $file['name']]),
                'status' => 'processing',
                'current_step' => 'upload', 
                'steps_json' => json_encode([['t' => date('H:i:s'), 'm' => 'File Uploaded and Sanitized']])
            ]);

            $this->json(['status' => 'success', 'job_id' => $jobId]);
        } catch (Exception $e) {
            $this->logger->error("Upload failed: " . $e->getMessage());
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}