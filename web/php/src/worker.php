<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Registry;
use App\Services\TextProcessor;
use App\Services\EmbeddingService;

/**
 * Updates the job record with a progress message and history.
 */
function logStep($db, int $jobId, string $message, array &$steps): void
{
    $timestamp = date('H:i:s');
    $steps[] = ['t' => $timestamp, 'm' => $message];

    // Keep history reasonable
    if (count($steps) > 50) {
        $steps = array_slice($steps, -50);
    }

    $db->execute("
        UPDATE jobs 
        SET 
            current_step = ?,
            steps_json   = ?,
            updated_at   = ?
        WHERE id = ?
    ", [
        $message,
        json_encode($steps, JSON_THROW_ON_ERROR),
        date('Y-m-d H:i:s'),
        $jobId
    ]);
}

$db = Registry::get('db');
$processor = new TextProcessor();
$vectorService = new EmbeddingService();

echo "🚀 Worker initialized. Monitoring 'jobs' for activity...\n";

while (true) {
    $jobs = $db->query("SELECT * FROM jobs WHERE status = 'pending' LIMIT 1");

    if (empty($jobs)) {
        sleep(2);
        continue;
    }

    $job = $jobs[0];
    $jobId = (int)$job['id'];
    $payload = json_decode($job['payload'], true);
    $steps = [];

    // Helper closure for internal logging
    $log = function(string $msg) use ($db, $jobId, &$steps) {
        echo "[$jobId] $msg\n";
        logStep($db, $jobId, $msg, $steps);
    };

    try {
        $log("📂 Opening file for staging...");
        
        if (!file_exists($payload['path'])) {
            throw new Exception("Source file missing: " . $payload['path']);
        }

        // Using a basic lines-to-units conversion (or your Stager service)
        $rawUnits = file($payload['path'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $log("🔍 Detected " . count($rawUnits) . " text units. Starting preparation...");

        foreach ($rawUnits as $index => $unit) {
            $meta = [
                'source' => $payload['original_name'],
                'unit'   => $index + 1,
            ];

            // Progress heartbeat every 10 units
            if ($index % 10 === 0 || $index === count($rawUnits) - 1) {
                $log(sprintf(
                    "🧹 Processing unit %d/%d (%s...)",
                    $index + 1,
                    count($rawUnits),
                    substr(trim($unit), 0, 30)
                ));
            }

            $preparedChunks = $processor->prepare($unit, $meta);

            foreach ($preparedChunks as $chunkIndex => $chunk) {
                // Unique identifier for the Vector DB
                $vectorId = sprintf("job_%d_u%d_c%d", $jobId, $index, $chunkIndex);

                // Sample preview for the HUD
                if ($chunkIndex === 0 && $index % 20 === 0) {
                    $preview = mb_substr($chunk, 0, 50) . '...';
                    $log("✨ Embedding sample: $preview");
                }

                // Call the Java Bridge
                $stored = $vectorService->store($chunk, $vectorId);
                
                if (!$stored) {
                    throw new Exception("Vector storage failed at unit $index");
                }
            }
        }

        $log("✅ All units processed and embedded. Job complete.");
        
        $db->execute("UPDATE jobs SET status = ?, finished_at = ? WHERE id = ?", 
            ['completed', date('Y-m-d H:i:s'), $jobId]);

    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
        $log("❌ Failed: " . substr($errorMsg, 0, 120));
        
        $db->execute("UPDATE jobs SET status = ?, error_log = ? WHERE id = ?", 
            ['failed', $errorMsg . "\n" . $e->getTraceAsString(), $jobId]);
    }
}