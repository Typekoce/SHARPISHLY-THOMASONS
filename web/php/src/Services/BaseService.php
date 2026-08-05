<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Location;
use RuntimeException;

abstract class BaseService 
{
    public string $uploadPath;
    public Location $location;
    protected string $aiEndpoint;
    protected string $logFile = PROJECT_ROOT . '/storage/logs/app.log';

    public function __construct() 
    {
        $this->location = new Location();
        $this->uploadPath = $this->location->storage('uploads');
        $this->aiEndpoint = getenv('AI_ENDPOINT') ?: 'http://ai:8000';
        
        $this->ensureDirectoryExists($this->uploadPath);
        $this->ensureDirectoryExists(dirname($this->logFile));
    }

    protected function postJson(string $url, array $data): array 
    {
        $payload = json_encode($data);
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("CURL Error during Handshake: $error", 'ERROR');
        }

        return [
            'code' => $httpCode,
            'body' => $response
        ];
    }

    private function ensureDirectoryExists(string $path): void 
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException("BaseService: Failed to create directory: $path");
            }
        }
    }

    protected function log(string $message, string $level = 'INFO', array $context = []): void 
    {
        $date = date('Y-m-d H:i:s');
        $jsonContext = !empty($context) ? ' ' . json_encode($context) : '';
        $formatted = "[$date] [$level] $message$jsonContext" . PHP_EOL;
        file_put_contents($this->logFile, $formatted, FILE_APPEND);
    }

    /**
     * Standard cURL execution for service integrations.
     */
    protected function curlRequest(string $url, string $method, array $headers = [], array $data = []): ?array
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