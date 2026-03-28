<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Location;
use RuntimeException;

class BaseService {
    public string $uploadPath;
    public Location $location;
    protected string $logFile = '/var/www/html/storage/log/app.log';

    public function __construct() {
        // Initialize the Location service first
        $this->location = new Location();
        
        // Use the instance to set the path
        $this->uploadPath = $this->location->storage('uploads');
        
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0775, true)) {
                throw new RuntimeException("Failed to create upload directory: $this->uploadPath");
            }
        }
        
        // Ensure the log directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException("Logger cannot create directory: $dir");
            }
        }
    }

    protected function log(string $message, string $level = 'INFO'): void {
        $date = date('Y-m-d H:i:s');
        $formatted = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $formatted, FILE_APPEND);
    }
}