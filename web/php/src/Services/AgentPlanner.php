<?php

declare(strict_types=1);

namespace App\Services;

class AgentPlanner
{
    private string $ollamaUrl;
    private string $ragUrl;

    public function __construct()
    {
        $env = function_exists('get_env') ? get_env() : [];
        $this->ollamaUrl = $env['OLLAMA_URL'] ?? 'http://127.0.0.1:11434';
        $this->ragUrl    = $env['RAG_URL'] ?? 'http://127.0.0.1/rag/ask';
    }

    public function generatePlan(string $instruction): ?array
    {
        // 1. Fetch RAG Context
        $ragReq = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode(['query' => $instruction]),
            'timeout' => 3
        ]]);
        
        $ragRes = @file_get_contents($this->ragUrl, false, $ragReq);
        if ($ragRes === false && function_exists('App\log')) {
            \App\log('RAG request failed', error_get_last());
        }
        
        $ragData = $ragRes ? json_decode($ragRes, true) : null;
        $context = $ragData['context'] ?? $ragData['answer'] ?? 'telephony, job_queue, logging';

        // 2. Prompt LLaMA 3.1 via Ollama (JSON Mode)
        $prompt = "You are a planning assistant for the Sharpishly system.\n" .
                  "User instruction: '{$instruction}'.\n" .
                  "Context: {$context}.\n" .
                  "Return ONLY a single JSON object with keys: agent_name, category, trigger, steps (array of objects with step, action, params).\n" .
                  "Do not include any text outside the JSON.";

        $ollamaReq = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode([
                'model'  => 'llama3.1',
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json'
            ]),
            'timeout' => 15
        ]]);

        $ollamaRes = @file_get_contents($this->ollamaUrl . '/api/generate', false, $ollamaReq);
        if ($ollamaRes === false) {
            if (function_exists('App\log')) {
                \App\log('Ollama request failed', error_get_last());
            }
            return null;
        }

        $result = json_decode($ollamaRes, true);
        $plan = json_decode($result['response'] ?? '', true);

        // Strict type & structure validation
        return (
            is_array($plan) &&
            !empty($plan['agent_name']) &&
            !empty($plan['steps']) &&
            is_array($plan['steps'])
        ) ? $plan : null;
    }
}
