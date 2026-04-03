<?php
# Inside your PHP Web Context

namespace App\Services;

class OllamaService {
    private $host = 'http://llm:11434';

    public function getStatus() {
        // We use a short timeout so we don't hang the Web request
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        
        $response = @file_get_contents($this->host . '/api/tags', false, $ctx);
        
        if ($response === false) {
            return ['active' => false, 'models' => []];
        }

        $data = json_decode($response, true);
        return [
            'active' => true,
            'models' => $data['models'] ?? []
        ];
    }
}