<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Registry;
use App\Services\Location;
use App\Services\ChunkingService;
use App\Services\EmbeddingService;

/**
 * Updates the HUD (Heads-Up Display) in the Surveyor SPA
 */
function logStep($db, int $jobId, string $message, array &$steps): void
{
    $timestamp = date('H:i:s');
    $steps[] = ['t' => $timestamp, 'm' => $message];

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

// 1. Initialize Thomasons Core Services
$db        = Registry::get('db');
$location  = Registry::make(Location::class);
$chunker   = Registry::make(ChunkingService::class);
$embedder  = Registry::make(EmbeddingService::class);

echo "🚀 Surveyor Worker Online. Monitoring root storage: " . $location->storage() . "\n";

while (true) {
    // 2. Poll for pending property reports
    $jobs = $db->query("SELECT * FROM jobs WHERE status = 'pending' LIMIT 1");

    if (empty($jobs)) {
        sleep(2); // Save CPU cycles
        continue;
    }

    $job = $jobs[0];
    $jobId = (int)$job['id'];
    $payload = json_decode($job['payload'], true);
    $steps = [];

    $log = function(string $msg) use ($db, $jobId, &$steps) {
        echo "[$jobId] $msg\n";
        logStep($db, $jobId, $msg, $steps);
    };

    try {
        $log("📂 Opening report for neural analysis...");
        
        // Ensure we are looking in the correct absolute path
        $filePath = $payload['path']; 
        
        if (!file_exists($filePath)) {
            throw new Exception("Source file missing in storage: " . $filePath);
        }

        // 3. Load Content (Handling large files via lines)
        $rawContent = file_get_contents($filePath);
        
        $log("🔍 Content loaded (" . strlen($rawContent) . " bytes). Beginning semantic chunking...");

        // 4. Use ChunkingService with Overlap (Critical for Cladding Context)
        $chunks = $chunker->split($rawContent, 800, 150);
        $totalChunks = count($chunks);

        foreach ($chunks as $index => $text) {
            // Heartbeat for the Surveyor Dashboard
            if ($index % 5 === 0 || $index === $totalChunks - 1) {
                $log(sprintf("🧠 Vectorizing segment %d/%d...", $index + 1, $totalChunks));
            }

            // 5. Generate Vector via Ollama + Java Bridge
            // We create a unique ID including the Job ID to allow bulk deletion if needed
            $vectorId = "prop_job_{$jobId}_c{$index}";
            
            // This calls your EmbeddingService -> Ollama -> Java findTopK/store
            $success = $embedder->store($text, $vectorId);
            
            if (!$success) {
                throw new Exception("Neural Bridge Timeout at segment $index");
            }
        }

        $log("✅ Neural Ingestion Complete. Data available for semantic search.");
        
        $db->execute("UPDATE jobs SET status = ?, finished_at = ? WHERE id = ?", 
            ['completed', date('Y-m-d H:i:s'), $jobId]);

    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
        $log("❌ Failed: " . substr($errorMsg, 0, 120));
        
        $db->execute("UPDATE jobs SET status = ?, error_log = ? WHERE id = ?", 
            ['failed', $errorMsg . "\n" . $e->getTraceAsString(), $jobId]);
    }
}