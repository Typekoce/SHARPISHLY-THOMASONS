<?php
declare(strict_types=1);

// worker.php
require_once __DIR__ . '/bootstrap.php';

use App\Core\Registry;
use App\Services\Logger;
use App\Services\Location;

// 1. Initialize Thomasons Core Services from Registry
$db     = Registry::get('db');
$log    = Registry::get('logger'); // Our new Logger class
$loc    = Registry::get(\App\Services\Location::class);

echo "🚀 Neural Worker Online. Monitoring: " . $loc->storage() . PHP_EOL;
$log->info("Worker Started", ['pid' => getmypid()]);

while (true) {
    // 2. Poll for pending jobs using the DB abstraction (No raw SQL)
    // Assuming your DB class has a query or fetch method that returns an array
    $jobs = $db->query("jobs", ['status' => 'pending'], "id ASC", 1);

    if (empty($jobs)) {
        sleep(3); // Save CPU cycles
        continue;
    }

    $job     = $jobs[0];
    $jobId   = (int)$job['id'];
    $payload = json_decode($job['payload'], true);
    
    $log->info("Job Picked Up", ['job_id' => $jobId, 'file' => $payload['name'] ?? 'unknown']);

    try {
        // 3. Update Job to 'processing'
        $db->update('jobs', ['status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')], $jobId);

        // 4. Validate File Existence
        $filePath = $payload['path'] ?? '';
        
        if (!$filePath || !file_exists($filePath)) {
            throw new Exception("File not found at path: " . $filePath);
        }

        // 5. Bare-Bones Simulation of the Pipeline
        $log->info("Processing File", ['path' => $filePath, 'size' => filesize($filePath)]);
        
        // --- This is where Chunking/Embedding will go tomorrow ---
        usleep(500000); // Simulate 0.5s of "work"
        // --------------------------------------------------------

        // 6. Mark as Complete
        $db->update('jobs', [
            'status'      => 'completed',
            'finished_at' => date('Y-m-d H:i:s')
        ], $jobId);

        $log->info("Job Success", ['job_id' => $jobId]);

    } catch (Throwable $e) {
        $log->error("Worker Job Failure", [
            'job_id' => $jobId,
            'error'  => $e->getMessage()
        ]);

        $db->update('jobs', [
            'status'    => 'failed',
            'error_log' => $e->getMessage()
        ], $jobId);
    }
}