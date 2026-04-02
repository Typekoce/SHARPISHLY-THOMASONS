<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class Logger extends BaseService
{
    /**
     * Core logging function - Signature matched to BaseService
     */
    public function log(string $message, string $level = 'INFO', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $jsonContext = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        
        // Ensure log file exists (inherited from BaseService/Location)
        $logFile = $this->logFile ?? '/var/www/html/storage/logs/app.log';

        // Formatted for the log file
        $formatted = sprintf("[%s] %s: %s%s%s", 
            $timestamp, 
            strtoupper($level), 
            $message, 
            $jsonContext, 
            PHP_EOL
        );

        // 1. Write with an exclusive lock (LOCK_EX) to prevent race conditions during high-volume neural tasks
        file_put_contents($logFile, $formatted, FILE_APPEND | LOCK_EX);

        // 2. Mirror ERRORS to Docker logs (stderr) for visibility in 'docker compose logs'
        if (in_array(strtolower($level), ['error', 'critical', 'alert', 'warning'])) {
            error_log("PHP_APP_STDOUT: [$level] $message" . $jsonContext);
        }
    }

    // PSR-3 Style Helper Methods
    public function info(string $message, array $context = []): void { $this->log($message, 'INFO', $context); }
    public function error(string $message, array $context = []): void { $this->log($message, 'ERROR', $context); }
    public function debug(string $message, array $context = []): void { $this->log($message, 'DEBUG', $context); }
    
    /**
     * Added specifically to resolve the Bootstrap Error in HealthController
     */
    public function warning(string $message, array $context = []): void { $this->log($message, 'WARNING', $context); }
}