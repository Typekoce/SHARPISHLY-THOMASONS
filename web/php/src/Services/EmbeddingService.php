<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use Exception;

/**
 * EmbeddingService - The Neural Bridge
 * Connects PHP to Ollama (Vector Generation) and Java (Vector Storage).
 */
class EmbeddingService 
{
    private Location $location;

    public function __construct()
    {
        $this->location = Registry::make(Location::class);
    }

    /**
     * Sends a text chunk to Ollama, then saves the resulting vector to Java.
     */
    public function store(string $text, string $id): bool 
    {
        try {
            // 1. Get the real vector from Ollama (Semantic representation)
            $vector = $this->getVectorOnly($text);
            $vectorCsv = implode(',', $vector);

            // 2. Resolve the Java Binary path 
            // If you eventually move the LLM bin to storage, use: $this->location->storage('llm/bin')
            $binPath = '/var/www/html/llm/foozie-vector-db/bin'; 
            
            // 3. Execute the CLI Bridge to the Java Vector DB
            $javaCmd = sprintf(
                'java -cp %s App add %s %s %s 2>&1',
                escapeshellarg($binPath),
                escapeshellarg($id),
                escapeshellarg($vectorCsv),
                escapeshellarg($text)
            );

            $output = (string)shell_exec($javaCmd);

            if (str_contains($output, 'SUCCESS')) {
                return true;
            }

            error_log("Java Bridge Failure: " . trim($output));
            return false;

        } catch (Exception $e) {
            error_log("Embedding Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches the actual semantic embedding from the Ollama container.
     */
    public function getVectorOnly(string $text): array
    {
        // Using the internal Docker network name 'ollama'
        $ch = curl_init("http://ollama:11434/api/embeddings");
        
        $payload = json_encode([
            "model" => "nomic-embed-text",
            "prompt" => $text
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5s timeout to prevent worker stalls
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Ollama connection failed (HTTP $httpCode). Ensure model 'nomic-embed-text' is pulled. Error: $error");
        }

        $data = json_decode($response, true);
        
        if (empty($data['embedding'])) {
            throw new Exception("Malformed response from Ollama. Check logs for model availability.");
        }

        return $data['embedding'];
    }
}