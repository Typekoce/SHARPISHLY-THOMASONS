<?php
declare(strict_types=1);

namespace App\Services;

/**
 * NEURAL SERVICE v3.1 - RAG ENABLED
 */
class NeuralService extends BaseService 
{
    /**
     * EXISTING: Handover for background ingestion (Phase 3/4)
     */
    public function ingest(string $filename, string $docId, array $meta = []): bool 
    {
        $fullPath = $this->uploadPath . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fullPath)) {
            $this->log("Neural Ingest Error: File not found", 'ERROR', ['doc_id' => $docId]);
            return false;
        }

        $response = $this->postJson("{$this->aiEndpoint}/ingest", [
            'file_path'   => $fullPath,
            'document_id' => $docId,
            'metadata'    => $meta
        ]);

        return ($response['code'] >= 200 && $response['code'] < 300);
    }

    /**
     * NEW: Real-time Vectorization (Phase 5 - RAG)
     * Communicates directly with Ollama to vectorize a search query.
     */
    public function getEmbedding(string $text): ?array 
    {
        // Using the internal Docker DNS for Ollama
        $url = "http://127.0.0.1-ollama:11434/api/embeddings";
        
        $payload = [
            "model" => "nomic-embed-text",
            "prompt" => $text
        ];

        // Using a simple cURL or file_get_contents via a helper
        $response = $this->postJson($url, $payload);

        if ($response['code'] !== 200) {
            $this->log("Embedding Error: " . ($response['body'] ?? 'Unknown'), 'ERROR');
            return null;
        }

        $data = json_decode($response['body'], true);
        return $data['embedding'] ?? null;
    }
}