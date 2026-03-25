<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Registry;
use App\Services\FileAgent;

class NeuralController extends BaseController {
    
    public function upload(): void {
        $db = Registry::get('db');
        $agent = new FileAgent(); // Or register in bootstrap if preferred

        try {
            if (!isset($_FILES['data_file'])) {
                $this->renderJson(['status' => 'error', 'message' => 'No file provided'], 400);
                return;
            }

            $filePath = $agent->receive($_FILES['data_file']);

            // Create a Job in MySQL
            $jobId = $db->save('jobs', [
                'status'     => 'pending',
                'file_path'  => $filePath,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->renderJson([
                'status' => 'success',
                'job_id' => $jobId,
                'message' => 'File ingested. Pipeline starting.'
            ]);
            
        } catch (\Exception $e) {
            $this->renderJson(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}