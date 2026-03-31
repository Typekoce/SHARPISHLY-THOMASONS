<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\VectorDb;
use App\Services\NeuralService;
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
            // Use the VectorDb to find meaning-based matches
            $contextRaw = $this->getNeuralContext($userMessage);

            // 2. AUGMENTED PROMPT
            $prompt = "Use the following context to answer the question. If the answer isn't in the context, say you don't know.\n\n";
            $prompt .= "--- CONTEXT ---\n" . ($contextRaw ?: "No relevant documents found.") . "\n---------------\n\n";
            $prompt .= "Question: " . $userMessage;

            // 3. GENERATION
            $response = $this->callNeuralEngine($prompt);

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

        // Convert question to vector first
        $queryVector = $neural->getEmbedding($query);
        
        if (!$queryVector) return "";

        // Find top 3 relevant chunks
        $matches = $vectorDb->search($queryVector, 3);
        
        return implode("\n", array_column($matches, 'content'));
    }

    private function callNeuralEngine(string $prompt): string
    {
        // Use the model we pulled earlier
        $model = "llama3.1";
        $url = "http://sharpishly-ollama:11434/api/generate";
        
        $payload = json_encode([
            "model" => $model, 
            "prompt" => $prompt,
            "system" => "You are a professional Thomasons assistant. Be concise.",
            "stream" => false
        ]);

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'timeout' => 60 // AI can be slow on a VM
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception("Ollama connection failed.");
        }

        $data = json_decode($result, true);
        return $data['response'] ?? 'No response from AI.';
    }
}
