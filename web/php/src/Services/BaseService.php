<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Location;
use RuntimeException;

/**
 * BASE SERVICE
 * Provides core infrastructure (Logging, Paths, Location) for all PHP Services.
 */
abstract class BaseService 
{
    /** @var string Path for document uploads */
    public string $uploadPath;
    
    /** @var Location Service for path resolution */
    public Location $location;
    
    /** @var string Primary application log path */
    protected string $logFile = '/var/www/html/storage/log/app.log';

    /**
     * Bootstraps service dependencies and ensures filesystem readiness.
     */
    public function __construct() 
    {
        // 1. Initialize the Location service
        $this->location = new Location();
        
        // 2. Resolve the dynamic storage path for uploads
        $this->uploadPath = $this->location->storage('uploads');
        
        // 3. Ensure upload directory exists with correct permissions
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0775, true) && !is_dir($this->uploadPath)) {
                throw new RuntimeException("BaseService: Failed to create upload directory: {$this->uploadPath}");
            }
        }
        
        // 4. Ensure the log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                throw new RuntimeException("BaseService: Cannot create log directory: {$logDir}");
            }
        }
    }

    /**
     * Standardized Log signature for the entire application.
     * Must match exactly in App\Services\Logger to avoid Fatal Errors.
     * * @param string $message The primary log message
     * @param string $level   Log level (INFO, ERROR, DEBUG, etc.)
     * @param array  $context Additional metadata to be JSON encoded
     */
    protected function log(string $message, string $level = 'INFO', array $context = []): void 
    {
        $date = date('Y-m-d H:i:s');
        
        // Convert context to JSON if it exists
        $jsonContext = !empty($context) 
            ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) 
            : '';

        $formatted = "[$date] [$level] $message$jsonContext" . PHP_EOL;
        
        // Use FILE_APPEND to prevent overwriting existing logs
        file_put_contents($this->logFile, $formatted, FILE_APPEND);
    }
}