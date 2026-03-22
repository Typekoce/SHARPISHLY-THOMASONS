<?php
// Inside your csv_ingest handler function

private function logStep(PDO $db, int $jobId, string $message, array &$steps): void
{
    $timestamp = date('H:i:s');
    $steps[] = ['t' => $timestamp, 'm' => $message];

    // Keep history reasonable (last 50 steps max to prevent bloat)
    if (count($steps) > 50) {
        $steps = array_slice($steps, -50);
    }

    $stmt = $db->prepare("
        UPDATE jobs 
        SET 
            current_step = :step,
            steps_json   = :steps_json,
            updated_at   = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        'step'       => $message,
        'steps_json' => json_encode($steps, JSON_THROW_ON_ERROR),
        'id'         => $jobId,
    ]);
}

// ────────────────────────────────────────────────
// Usage in handleCsvIngest()
$steps = [];
$logStep = function(string $msg) use ($db, $jobId, &$steps) {
    $this->logStep($db, $jobId, $msg, $steps);
};

try {
    $logStep("📂 Opening file for staging...");
    $rawUnits = $stager->stage($payload['path']);

    $logStep("🔍 Detected " . count($rawUnits) . " text units. Starting preparation...");

    $processor = Registry::get(TextProcessor::class);

    foreach ($rawUnits as $index => $unit) {
        $meta = [
            'source' => $payload['original_name'],
            'unit'   => $index + 1,
        ];

        if ($index % 10 === 0 || $index === count($rawUnits) - 1) {
            $logStep(sprintf(
                "🧹 Processing unit %d/%d (%s...)",
                $index + 1,
                count($rawUnits),
                substr($unit, 0, 40)
            ));
        }

        $preparedChunks = $processor->prepare($unit, $meta);

        foreach ($preparedChunks as $chunkIndex => $chunk) {
            // Optional: log first chunk preview only
            if ($chunkIndex === 0 && $index % 20 === 0) {
                $preview = substr($chunk, 0, 60) . (strlen($chunk) > 60 ? '...' : '');
                $logStep("✨ Sample prepared: $preview");
            }

            // → Here: actual embedding + vector storage call
            // e.g. $vectorService->embedAndStore($chunk, $meta);
        }
    }

    $logStep("✅ All units processed and embedded. Job complete.");
    // Mark job as completed
    $db->prepare("UPDATE jobs SET status = 'completed', finished_at = NOW() WHERE id = ?")
       ->execute([$jobId]);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage() . "\n" . $e->getTraceAsString();
    $db->prepare("UPDATE jobs SET status = 'failed', error_log = :err WHERE id = ?")
       ->execute(['err' => $errorMsg, 'id' => $jobId]);
    $logStep("❌ Failed: " . substr($e->getMessage(), 0, 120));
}