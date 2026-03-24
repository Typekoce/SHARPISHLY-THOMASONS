<?php
declare(strict_types=1);

namespace App\Services;

class EmbeddingService 
{
    /**
     * Sends a text chunk and its vector to the Java Vector DB.
     * * @param string $text The prepared text from TextProcessor
     * @param string $id   Unique identifier (e.g., "sales.csv_u10_c0")
     * @return bool        True if Java confirmed SUCCESS
     */
    public function store(string $text, string $id): bool 
    {
        // --- ATTEMPT 1: Generate Mock Vector ---
        // 1536 is standard for OpenAI/Ollama; 384 is common for local BERT models.
        $dimensions = 1536; 
        $vector = array_map(fn() => (mt_rand() / mt_getrandmax()), range(1, $dimensions));
        $vectorCsv = implode(',', $vector);

        // --- ATTEMPT 2: The CLI Bridge to Java ---
        // Path matches your current project structure in Docker
        $binPath = '/var/www/html/llm/foozie-vector-db/bin';
        
        // We use escapeshellarg on every param to ensure pipes (|) and spaces
        // in the $text don't break the shell command or the Java parser.
        $javaCmd = sprintf(
            'java -cp %s App add %s %s %s 2>&1',
            escapeshellarg($binPath),
            escapeshellarg($id),
            escapeshellarg($vectorCsv),
            escapeshellarg($text)
        );

        $output = (string)shell_exec($javaCmd);

        // --- ATTEMPT 3: Validation & Error Logging ---
        if (str_contains($output, 'SUCCESS')) {
            return true;
        }

        // If it fails, we log the Java error so we aren't flying blind
        error_log("Java Bridge Failure: " . trim($output));
        return false;
    }
}