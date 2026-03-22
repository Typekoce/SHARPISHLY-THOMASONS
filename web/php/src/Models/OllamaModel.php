<?php declare(strict_types=1);

namespace App\Models;

use App\Services\Logger;

class OllamaModel {
    // 1. Changed to the host-gateway alias defined in your docker-compose.yml
    private string $host = 'http://host.docker.internal:11434';
    private string $chatModel = 'llama3.2:3b';
    private string $embedModel = 'nomic-embed-text';

    public function generate(string $prompt): string {

        if (getenv('APP_ENV') === 'dev') {
            // Instant return for UI testing
            return "[MOCK] AI Brain is currently in sleep mode to save VM resources. Received: " . substr($prompt, 0, 15);
        }

        $payload = [
            'model'  => $this->chatModel,
            'prompt' => $prompt,
            'stream' => false
        ];

        $ch = curl_init("{$this->host}/api/generate");
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, // Timeout if server is down
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $resp = curl_exec($ch);
        
        // 2. Added Error Check: Stop the crash if cURL fails
        if ($resp === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return "Error: Could not reach Ollama at {$this->host} ($error)";
        }

        curl_close($ch);
        $data = json_decode((string)$resp, true);
        return trim($data['response'] ?? 'No answer');
    }

    public function embed(string $text): array {
        $payload = ['model' => $this->embedModel, 'prompt' => $text];
        $ch = curl_init("{$this->host}/api/embeddings");
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $resp = curl_exec($ch);
        
        if ($resp === false) {
            curl_close($ch);
            return [];
        }

        curl_close($ch);
        $data = json_decode((string)$resp, true);
        return $data['embedding'] ?? [];
    }

    public function ragAsk(string $question): string {
        // 3. Ensure this directory exists relative to the Model file
        $docsDir = __DIR__ . '/documents/';
        if (!is_dir($docsDir)) {
            mkdir($docsDir, 0777, true);
        }

        $docs = glob($docsDir . '*.txt');
        $context = "";

        if ($docs) {
            foreach ($docs as $doc) {
                $content = file_get_contents($doc);
                if ($content && stripos($content, $question) !== false) {
                    $context .= "\n\nFrom " . basename($doc) . ":\n" . substr($content, 0, 800);
                }
            }
        }

        $prompt = "Context:\n$context\n\nQuestion: $question\n\nAnswer concisely using only the context if possible.";

        return $this->generate($prompt);
    }
}