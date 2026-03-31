<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Location;
use RuntimeException;

/**
 * BASE SERVICE
 * Provides core infrastructure (Logging, Paths, Networking) for all PHP Services.
 */
abstract class BaseService 
{
    /** @var string Path for document uploads */
    public string $uploadPath;
    
    /** @var Location Service for path resolution */
    public Location $location;
    
    /** @var string AI Engine DNS Endpoint */
    protected string $aiEndpoint;
    
    /** @var string Primary application log path */
    protected string $logFile = '/var/www/html/storage/log/app.log';

    /**
     * Bootstraps service dependencies and ensures filesystem readiness.
     */
    public function __construct() 
    {
        $this->location = new Location();
        $this->uploadPath = $this->location->storage('uploads');
        
        // RECALL: Standardized service name 'ai' for Docker DNS
        $this->aiEndpoint = getenv('AI_ENDPOINT') ?: 'http://ai:8000';
        
        $this->ensureDirectoryExists($this->uploadPath);
        $this->ensureDirectoryExists(dirname($this->logFile));
    }

    /**
     * Executes a JSON POST request to the AI Engine (The Handshake).
     */
    protected function postJson(string $url, array $data): array 
    {
        $payload = json_encode($data);
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30, // Shorter timeout for the initial trigger
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

    /**
     * Utility to ensure paths exist.
     */
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
}