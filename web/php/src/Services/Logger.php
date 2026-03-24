<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use RuntimeException;

class Logger
{
    private string $logFile;
    private Location $location;

    public function __construct()
    {
        $this->location = Registry::make(Location::class);
        $this->logFile = $this->location->logs('app.log');

        // Ensure the directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException("Logger cannot create directory: $dir");
            }
        }
    }

    /**
     * Core logging function
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $jsonContext = !empty($context) ? ' ' . json_encode($context) : '';
        $formatted = sprintf("[%s] %s: %s%s%s", $timestamp, strtoupper($level), $message, $jsonContext, PHP_EOL);

        // 1. Write to our persistent file in /storage/logs
        file_put_contents($this->logFile, $formatted, FILE_APPEND | LOCK_EX);

        // 2. Mirror ERRORS to Docker logs for immediate visibility
        if (in_array(strtolower($level), ['error', 'critical', 'alert'])) {
            error_log("PHP_APP_ERROR: $message" . $jsonContext);
        }
    }

    public function info(string $message, array $context = []): void { $this->log('info', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('error', $message, $context); }
    public function debug(string $message, array $context = []): void { $this->log('debug', $message, $context); }
}