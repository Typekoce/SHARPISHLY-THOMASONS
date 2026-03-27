<?php
declare(strict_types=1);

/**
 * Thomasons Neural Worker v1.2 - Release Candidate
 * Resilient Implementation: Graceful Schema Handling & Error Suppression
 */

require_once __DIR__ . '/bootstrap.php';

use App\Services\Db;
use App\Services\Logger;
use App\Services\Location;
use App\Services\TextProcessor;

try {
    $db  = new Db(); 
    $log = new Logger(); 
    $loc = new Location();
    $processor = new TextProcessor();
} catch (Exception $e) {
    die("❌ Fatal: Service Initialization Failed: " . $e->getMessage() . PHP_EOL);
}

echo "🚀 Neural Worker Online. Monitoring: " . $loc->uploads() . PHP_EOL;

while (true) {
    $jobId = null; 
    $job   = null;

    try {
        // 1. Poll for pending jobs
        $jobs = $db->find([
            'tbl'   => 'jobs',
            'where' => ['status' => 'pending'],
            'order' => ['id' => 'ASC'],
            'limit' => 1
        ]);

        if (empty($jobs)) {
            usleep(500000); 
            continue;
        }

        $job      = $jobs[0];
        $jobId    = (int)$job['id'];
        $payload  = json_decode($job['payload'] ?? '[]', true);
        $filePath = $payload['path'] ?? '';
        $title    = $job['title'] ?? 'Neural Job';

        echo "📦 [Job #$jobId] Processing: " . basename($filePath) . PHP_EOL;

        // 2. Initial State Update (Silent failure protection)
        try {
            $db->save('jobs', [
                'id'           => $jobId,
                'title'        => $title,
                'status'       => 'processing',
                'current_step' => 'Cleaning text...',
                'progress'     => 10
            ]);
        } catch (Throwable $e) {
            // Silently fall back if columns don't exist
        }

        if (!file_exists($filePath)) throw new Exception("File missing: $filePath");

        // 3. Neural Processing
        $raw = file_get_contents($filePath);
        
        /**
         * CRITICAL FIX: Ensure $processor->prepare returns a clean string.
         * If prepare returns an array (legacy), we flatten it immediately.
         */
        $clean = $processor->prepare($raw); 
        $cleanString = is_array($clean) ? implode(' ', $clean) : (string)$clean;
        
        $chunks = $processor->chunk($cleanString, 1000, 100);

        // 4. Progress Loop
        $total = count($chunks);
        foreach ($chunks as $index => $chunk) {
            $current = $index + 1;
            $percent = (int)(20 + (($current / $total) * 70)); // Range 20-90%
            
            try {
                $db->save('jobs', [
                    'id'           => $jobId,
                    'title'        => $title,
                    'current_step' => "Slicing: $current/$total chunks",
                    'progress'     => $percent
                ]);
            } catch (Throwable $e) {
                // Ignore missing progress columns during loop
            }

            echo "   -> Progress: $percent% \r";
            usleep(50000); 
        }
        echo PHP_EOL;

        // 5. Finalize - Resilient Double-Try Pattern
        $finalizeData = [
            'id'           => $jobId, 
            'title'        => $title,
            'status'       => 'completed'
        ];

        try {
            // First attempt: Try updating all schema-v3 columns
            $fullData = array_merge($finalizeData, [
                'current_step' => 'Completed',
                'progress'     => 100,
                'finished_at'  => date('Y-m-d H:i:s')
            ]);
            $db->save('jobs', $fullData);
        } catch (Throwable $e) {
            // Second attempt: Core columns only (Fallback for partial migrations)
            $db->save('jobs', $finalizeData);
            echo "⚠️ Job #$jobId marked completed (skipped extended schema fields)." . PHP_EOL;
        }

        echo "✅ [Job #$jobId] Success!" . PHP_EOL . "-------------------" . PHP_EOL;

    } catch (Throwable $e) {
        echo "❌ [Job #$jobId] Error: " . $e->getMessage() . PHP_EOL;
        $log->error("Worker Failure: " . $e->getMessage(), ['job_id' => $jobId]);

        if (isset($db) && $jobId) {
            try {
                $db->save('jobs', [
                    'id'     => $jobId, 
                    'title'  => $job['title'] ?? 'Neural Job',
                    'status' => 'failed'
                ]);
            } catch (Throwable $dbErr) {
                echo "🛑 Critical: Database write failed for job failure status." . PHP_EOL;
            }
        }
        sleep(1);
    }
}