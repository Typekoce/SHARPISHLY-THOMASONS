<?php

namespace App\Controllers;

/**
 * ApiAiServicesController
 * Minimalist HTTP router for API calls across cloud providers and AI services.
 */
class ApiAiServicesController extends BaseApiController
{
    private array $endpoints = [
        'AWS'          => 'https://ec2.us-east-1.amazonaws.com',
        'Xero'         => 'https://api.xero.com/api.xro/2.0',
        'Azure'        => 'https://management.azure.com',
        'AzureFoundry' => 'https://{resource}.openai.azure.com/openai/v1',
        'ChatGPT'      => 'https://api.openai.com/v1/chat/completions',
        'Claude'       => 'https://api.anthropic.com/v1/messages',
        'Gemini'       => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        'Grok'         => 'https://api.x.ai/v1/chat/completions',
        'Kimi'         => 'https://api.moonshot.cn/v1/chat/completions',
        'DeepSeek'     => 'https://api.deepseek.com/v1/chat/completions',
        'Ollama'       => 'http://localhost:11434/api/generate',
        'Mistral'      => 'https://api.mistral.ai/v1/chat/completions',
        'Cohere'       => 'https://api.cohere.com/v2/chat',
    ];

    private array $actions = [
        'create' => 'POST',
        'read'   => 'GET',
        'update' => 'PUT',
        'delete' => 'DELETE',
    ];

    /**
     * Executes an API request based on $conditions.
     */
    public function execute(array $conditions)
    {
        $source = $conditions['source'] ?? null;
        if (!$source || !isset($this->endpoints[$source])) {
            return $this->json(['error' => 'invalid_source', 'message' => "Source {$source} not supported"], 400);
        }

        $action = strtolower($conditions['action'] ?? 'read');
        $method = $conditions['method'] ?? $this->actions[$action] ?? 'GET';
        $url    = $this->endpoints[$source];

        // Replace template placeholders if present
        if (isset($conditions['resource'])) {
            $url = str_replace('{resource}', $conditions['resource'], $url);
        }
        if (isset($conditions['model'])) {
            $url = str_replace('{model}', $conditions['model'], $url);
        }

        // Append custom endpoint route or ID
        if (!empty($conditions['endpoint'])) {
            $url = rtrim($url, '/') . '/' . ltrim($conditions['endpoint'], '/');
        }
        if (!empty($conditions['id'])) {
            $url = rtrim($url, '/') . '/' . $conditions['id'];
        }

        // Build query string
        $params = $conditions['params'] ?? [];
        if (($apiKey = $conditions['api_key'] ?? null) && $source === 'Gemini') {
            $params['key'] = $apiKey;
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Build headers
        $headers = $conditions['headers'] ?? ['Content-Type: application/json'];
        $token   = $conditions['token'] ?? $conditions['api_key'] ?? null;

        if ($token) {
            if ($source === 'Claude') {
                $headers[] = "x-api-key: {$token}";
                $headers[] = 'anthropic-version: 2023-06-01';
            } elseif ($source !== 'Gemini') {
                $headers[] = "Authorization: Bearer {$token}";
            }
        }

        $response = $this->curlRequest($url, $method, $headers, $conditions['data'] ?? []);

        return $this->json($response ?? ['error' => 'request_failed'], $response ? 200 : 502);
    }

    /**
     * Standard cURL execution mirroring framework controllers.
     */
    private function curlRequest(string $url, string $method, array $headers = [], array $data = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 15,
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $raw   = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['raw' => $raw];
    }
}