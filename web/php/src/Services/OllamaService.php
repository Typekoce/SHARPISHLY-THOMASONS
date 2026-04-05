<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * OLLAMA SERVICE
 * High-level interface for the LLM. 
 * Uses EnvironmentService (SSOT) to discover its own infrastructure.
 */
class OllamaService extends BaseService {
    private string $host;
    private int $timeout;
    private EnvironmentService $env;
    private Location $location;

    public function __construct() {
        $this->env = EnvironmentService::getInstance();
        $this->location = new Location();
        
        // Host Discovery: Pull from .env or Docker service 'llm'
        $this->host = $this->env->get('OLLAMA_HOST', 'http://llm:11434');

        // Dynamic Timeout: Parse '120s' from Docker Compose start_period
        $rawPeriod = $this->env->get('DB_START_PERIOD', '5s');
        $this->timeout = (int) filter_var($rawPeriod, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Checks if the LLM container is responsive and lists models.
     */
    public function getStatus(): array {
        $endpoint = rtrim($this->host, '/') . '/api/tags';
        
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'header' => "Content-type: application/json\r\n"
            ]
        ]);

        $response = @file_get_contents($endpoint, false, $ctx);
        
        if ($response === false) {
            return [
                'active'  => false,
                'host'    => $this->host,
                'timeout' => $this->timeout,
                'error'   => "Unable to reach LLM at {$endpoint}"
            ];
        }

        $data = json_decode($response, true);
        
        return [
            'active' => true,
            'models' => $data['models'] ?? [],
            'system_version' => $this->env->get('SYS_VERSION', '1.0.0') // From Makefile
        ];
    }

    /**
     * Generates embeddings for a given string.
     * This is used for the RAG 'Healing Factor' later.
     */
    public function getEmbedding(string $text, string $model = 'nomic-embed-text'): array {
        $endpoint = rtrim($this->host, '/') . '/api/embeddings';
        
        $payload = json_encode([
            'model' => $model,
            'prompt' => $text
        ]);

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/json\r\n",
                'content' => $payload,
                'timeout' => 30 // Embeddings take longer than a simple status ping
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($endpoint, false, $context);

        if ($result === false) {
            return ['error' => 'Embedding generation failed'];
        }

        return json_decode($result, true);
    }
}