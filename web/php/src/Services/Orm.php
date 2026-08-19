<?php

declare(strict_types=1);

namespace App\Services;

class Orm extends BaseService
{
    private array $endpoints = [
        'AWS'           => 'https://ec2.us-east-1.amazonaws.com',
        'AwsHelloWorld' => 'https://w4ygtkgcmijy7noprpz4mvxofq0qecmu.lambda-url.eu-north-1.on.aws/',
        'Xero'          => 'https://api.xero.com/api.xro/2.0',
        'Azure'         => 'https://management.azure.com',
        'AzureHelloWorld' => 'https://app-sharpishly-azure.azurewebsites.net/api/health',
        'AzureFoundry'  => 'https://{resource}.openai.azure.com/openai/v1',
        'ChatGPT'       => 'https://api.openai.com/v1/chat/completions',
        'Claude'        => 'https://api.anthropic.com/v1/messages',
        'Gemini'        => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        'Grok'          => 'https://api.x.ai/v1/chat/completions',
        'Kimi'          => 'https://api.moonshot.cn/v1/chat/completions',
        'DeepSeek'      => 'https://api.deepseek.com/v1/chat/completions',
        'Ollama'        => 'http://localhost:11434/api/generate',
        'Mistral'       => 'https://api.mistral.ai/v1/chat/completions',
        'Cohere'        => 'https://api.cohere.com/v2/chat',

        'Square'          => 'https://connect.squareup.com/v2/reports/sales',
        'OpenTable'       => 'https://api.opentable.com/v2/bookings',
        'Eventbrite'      => 'https://www.eventbriteapi.com/v3/organizations/me/events/',
        'ClickUp'         => 'https://api.clickup.com/api/v2/team',
        'GoogleCal'       => 'https://www.googleapis.com/calendar/v3/calendars/primary/events',
        'EventBriteHello' => 'https://www.eventbriteapi.com/v3/users/me/',
    ];

    private array $actions = [
        'create' => 'POST',
        'read'   => 'GET',
        'update' => 'PUT',
        'delete' => 'DELETE',
    ];

    public function execute(array $conditions): array
    {
        $source = $conditions['source'] ?? null;
        if (!$source || !isset($this->endpoints[$source])) {
            return ['error' => 'invalid_source', 'message' => "Source {$source} not supported"];
        }

        $action = strtolower($conditions['action'] ?? 'read');
        $method = $conditions['method'] ?? $this->actions[$action] ?? 'GET';
        $url    = $this->endpoints[$source];

        if (isset($conditions['resource'])) {
            $url = str_replace('{resource}', $conditions['resource'], $url);
        }
        if (isset($conditions['model'])) {
            $url = str_replace('{model}', $conditions['model'], $url);
        }
        if (!empty($conditions['endpoint'])) {
            $url = rtrim($url, '/') . '/' . ltrim($conditions['endpoint'], '/');
        }
        if (!empty($conditions['id'])) {
            $url = rtrim($url, '/') . '/' . $conditions['id'];
        }

        $params = $conditions['params'] ?? [];
        if (($apiKey = $conditions['api_key'] ?? null) && $source === 'Gemini') {
            $params['key'] = $apiKey;
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = $conditions['headers'] ?? ['Content-Type: application/json'];

        $envConfig = function_exists('get_env') ? get_env() : [];
        $token     = $conditions['token']
                  ?? $conditions['api_key']
                  ?? (defined('EVENTBRITE_TOKEN') ? EVENTBRITE_TOKEN : ($envConfig['eventbrite_token'] ?? null));

        if ($token) {
            if ($source === 'Claude') {
                $headers[] = "x-api-key: {$token}";
                $headers[] = 'anthropic-version: 2023-06-01';
            } elseif ($source !== 'Gemini') {
                $headers[] = "Authorization: Bearer {$token}";
            }
        }

        return $this->curlRequest($url, $method, $headers, $conditions['data'] ?? []) ?? ['error' => 'request_failed'];
    }

    public function executeParallel(array $sources): array
    {
        $results = [];

        foreach ($sources as $alias => $source) {
            $key = is_string($alias) ? $alias : $source;
            $url = $this->endpoints[$source] ?? $source;

            $results[$key] = $this->curlRequest($url, 'GET');
        }

        return $results;
    }
}