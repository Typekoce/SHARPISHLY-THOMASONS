<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\VectorDb;
use App\Services\NeuralService;
use App\Services\PromptService; // New Service
use Exception;

class ChatController extends BaseController
{
    public function ask(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';

        if (empty($userMessage)) {
            $this->json(['error' => 'Question is required'], 400);
            return;
        }

        try {
            // 1. SEMANTIC RETRIEVAL
            $contextRaw = $this->getNeuralContext($userMessage);

            // 2. AUGMENTED PROMPT (Using PromptService)
            $systemInstructions = PromptService::getSystemInstructions();
            $finalPrompt = PromptService::buildRagPrompt($userMessage, $contextRaw);

            // 3. GENERATION
            $response = $this->callNeuralEngine($finalPrompt, $systemInstructions);

            $this->json([
                'answer' => $response,
                'has_context' => !!$contextRaw,
                'status' => 'success'
            ]);

        } catch (Exception $e) {
            $this->logger->log("Chat Error: " . $e->getMessage(), 'ERROR');
            $this->json(['error' => 'Neural engine is currently offline. Please try again later.'], 500);
        }
    }

    private function getNeuralContext(string $query): string
    {
        $neural = new NeuralService();
        $vectorDb = new VectorDb();

        $queryVector = $neural->getEmbedding($query);
        if (!$queryVector) return "";

        $matches = $vectorDb->search($queryVector, 3);
        return implode("\n", array_column($matches, 'content'));
    }

    private function callNeuralEngine(string $prompt, string $system): string
    {
        $url = "http://127.0.0.1-ollama:11434/api/generate";
        
        $payload = json_encode([
            "model" => "llama3.1", 
            "prompt" => $prompt,
            "system" => $system,
            "stream" => false
        ]);

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'timeout' => 90 // Increased timeout for heavier RAG prompts
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception("Ollama connection failed or timed out.");
        }

        $data = json_decode($result, true);
        return $data['response'] ?? 'No response from AI.';
    }
}