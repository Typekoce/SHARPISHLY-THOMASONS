<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class Logger extends BaseService
{
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