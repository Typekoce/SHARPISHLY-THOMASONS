<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\EmbeddingService;
use Exception;

class ChatController extends BaseController
{
    private EmbeddingService $embedder;

    public function __construct()
    {
        parent::__construct();
        $this->embedder = new EmbeddingService();
    }

    /**
     * POST /php/chat
     * Input: { "message": "What is the company policy on remote work?" }
     */
    public function ask(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';

        if (empty($userMessage)) {
            $this->json(['error' => 'Message is required'], 400);
            return;
        }

        try {
            // 1. RETRIEVAL: Find relevant context from Java Vector DB
            $queryVector = $this->embedder->getVectorOnly($userMessage);
            $vectorCsv = implode(',', $queryVector);

            $binPath = '/var/www/html/llm/foozie-vector-db/bin';
            $javaCmd = sprintf('java -cp %s App search %s 3 2>&1', escapeshellarg($binPath), escapeshellarg($vectorCsv));
            $contextRaw = shell_exec($javaCmd);

            // 2. AUGMENTATION: Build the "Neural Prompt"
            $prompt = "You are a helpful assistant. Use the following context to answer the user's question.\n";
            $prompt .= "Context:\n" . ($contextRaw ?: "No specific context found.") . "\n\n";
            $prompt .= "Question: " . $userMessage . "\n";
            $prompt .= "Answer:";

            // 3. GENERATION: Ask Ollama (using a chat model like llama3)
            $response = $this->callOllamaGenerate($prompt);

            $this->json([
                'answer' => $response,
                'context_used' => !!$contextRaw,
                'status' => 'success'
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function callOllamaGenerate(string $prompt): string
    {
        $ch = curl_init("http://ollama:11434/api/generate");
        
        $payload = json_encode([
            "model" => "llama3", // Ensure this model is pulled
            "prompt" => $prompt,
            "stream" => false
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['response'] ?? "I'm sorry, I couldn't generate an answer.";
    }
}