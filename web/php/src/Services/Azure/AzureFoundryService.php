<?php

namespace App\Services\Azure;

use App\Services\BaseService;

class AzureFoundryService extends BaseService {

    /**
     * Executes a completions request against the Azure AI Foundry endpoint.
     */
    public function sendCompletion(array $auth, string $prompt): array {
        $payload = [
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'You are a helpful assistant within the Sharpishly application framework.'
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens'  => 500
        ];

        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $auth['api_key']
        ];

        $response = $this->executeCurl($auth['endpoint'], 'POST', json_encode($payload), $headers);

        if (!$response) {
            $this->log('No response received from Azure AI Foundry service.', 'ERROR');
            return [
                'success' => false,
                'error'   => 'No response received from Azure AI Foundry service.'
            ];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('Failed to parse JSON response from Azure: ' . json_last_error_msg(), 'ERROR');
            return [
                'success' => false,
                'error'   => 'Failed to parse JSON response from Azure: ' . json_last_error_msg(),
                'raw'     => $response
            ];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if ($content !== null) {
            return [
                'success' => true,
                'result'  => $content,
                'usage'   => $decoded['usage'] ?? []
            ];
        }

        return [
            'success' => false,
            'error'   => 'Model response payload did not contain expected completion choice.',
            'raw'     => $decoded
        ];
    }

    /**
     * cURL wrapper adhering to zero-external-dependency policy
     */
    private function executeCurl(string $url, string $method, string $body, array $headers): string|bool {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("cURL error calling Azure AI Foundry: $error", 'ERROR');
        }

        return $result;
    }
}