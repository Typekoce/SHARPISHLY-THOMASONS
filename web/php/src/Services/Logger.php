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
        
        // Formatted for the log file
        $formatted = sprintf("[%s] %s: %s%s%s", 
            $timestamp, 
            strtoupper($level), 
            $message, 
            $jsonContext, 
            PHP_EOL
        );

        // 1. Write to our persistent file with an exclusive lock to prevent corruption
        file_put_contents($this->logFile, $formatted, FILE_APPEND | LOCK_EX);

        // 2. Mirror ERRORS to Docker logs (stderr) for immediate visibility in 'docker compose logs'
        if (in_array(strtolower($level), ['error', 'critical', 'alert'])) {
            error_log("PHP_APP_ERROR: $message" . $jsonContext);
        }
    }

    // Helper methods now pass arguments in the correct order to match the new signature
    public function info(string $message, array $context = []): void { $this->log($message, 'info', $context); }
    public function error(string $message, array $context = []): void { $this->log($message, 'error', $context); }
    public function debug(string $message, array $context = []): void { $this->log($message, 'debug', $context); }
}