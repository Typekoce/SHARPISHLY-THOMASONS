<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;

class ChatController extends BaseController
{
    /**
     * POST /php/chat
     * Inherits $this->db, $this->logger, $this->loc from BaseController.
     */
    public function ask(): void
    {
        // Use a helper or native input retrieval
        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';

        if (empty($userMessage)) {
            $this->json(['error' => 'Question is required'], 400);
        }

        try {
            // 1. RETRIEVAL & AUGMENTATION (Handed off to our Python/Ollama Ecosystem)
            // Instead of raw Java shell_exec, we hit the internal Ollama API 
            // and include our vector-search context via the Python-populated DB.
            
            $contextRaw = $this->getNeuralContext($userMessage);

            $prompt = "Context: " . ($contextRaw ?: "No internal docs found.") . "\n\n";
            $prompt .= "Question: " . $userMessage;

            // 2. GENERATION (Lean Service Call)
            $response = $this->callNeuralEngine($prompt);

            $this->json([
                'answer' => $response,
                'has_context' => !!$contextRaw,
                'status' => 'success'
            ]);

        } catch (Exception $e) {
            $this->logger->error("Chat Error: " . $e->getMessage());
            $this->json(['error' => 'Neural engine timeout. Try again.'], 500);
        }
    }

    /**
     * Logic: Query the Vector DB (handled by the Python-mirrored tables)
     */
    private function getNeuralContext(string $query): string
    {
        // For v1.0, we pull the most recent relevant chunks from our vectors table
        // that the Python worker just populated.
        $results = $this->db->select(
            "SELECT content FROM vectors WHERE content LIKE ? LIMIT 3", 
            ["%$query%"]
        );

        return implode("\n", array_column($results, 'content'));
    }

    /**
     * Logic: Communication with the Ollama Docker Container
     */
    private function callNeuralEngine(string $prompt): string
    {
        // Using the internal Docker DNS 'sharpishly-ollama' defined in your compose
        $url = "http://sharpishly-ollama:11434/api/generate";
        
        $payload = json_encode([
            "model" => "llama3", 
            "prompt" => $prompt,
            "system" => "You are a helpful Thomasons assistant. Use the provided context.",
            "stream" => false
        ]);

        // Keep it lean: Use file_get_contents with context for a DRYer alternative to CURL
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $payload,
            ],
        ];
        
        $result = file_get_contents($url, false, stream_context_create($options));
        $data = json_decode($result, true);

        return $data['response'] ?? "I'm sorry, I couldn't reach the brain.";
    }
}