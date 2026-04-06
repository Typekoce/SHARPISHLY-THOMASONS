<?php

declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * OLLAMA SERVICE
 * High-level interface for the LLM. 
 */
class OllamaService extends BaseService 
{
    private string $host;
    private int $timeout;
    private EnvironmentService $env;

    /**
     * The Constructor now correctly bootstraps via BaseService
     */
    public function __construct() 
    {
        // 1. Initialize Parent (Paths, Logging, Location)
        parent::__construct(); 
        
        $this->env = EnvironmentService::getInstance();
        
        // 2. Discover LLM Infrastructure
        $this->host = $this->env->get('OLLAMA_HOST', 'http://llm:11434');

        // 3. Dynamic Timeout logic remains intact
        $rawPeriod = $this->env->get('DB_START_PERIOD', '5s');
        $this->timeout = (int) filter_var($rawPeriod, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Checks if the LLM container is responsive.
     * Logic preserved from previous version.
     */
    public function getStatus(): array 
    {
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
                'error'   => "Unable to reach LLM at {$endpoint}"
            ];
        }

        $data = json_decode($response, true);
        
        return [
            'active' => true,
            'models' => $data['models'] ?? [],
            'system_version' => $this->env->get('SYS_VERSION', '1.0.0')
        ];
    }

    /**
     * Generates embeddings for the RAG 'Healing Factor'.
     * Logic preserved from previous version.
     */
    public function getEmbedding(string $text, string $model = 'nomic-embed-text'): array 
    {
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
                'timeout' => 30 
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