<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

class Diagnostics
{
    private Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? ($GLOBALS['logger'] ?? new Logger());
        $this->registerHandlers();
    }

    /**
     * Registers exception, error, and shutdown hooks
     */
    private function registerHandlers(): void
    {
        // 1. Capture Uncaught Exceptions
        set_exception_handler(function (Throwable $e) {
            $this->logger->error("Uncaught Exception: " . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'code'  => $e->getCode(),
                'trace' => explode("\n", $e->getTraceAsString())
            ]);

            $this->respondWithError('Internal Server Error', $e->getMessage(), $e->getFile(), $e->getLine());
        });

        // 2. Capture Fatal PHP Errors (e.g. Missing Class, Undefined Method)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $this->logger->error("Fatal PHP Error: {$error['message']}", [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type']
                ]);

                $this->respondWithError('Fatal PHP Error', $error['message'], $error['file'], $error['line']);
            }
        });

        // 3. Convert Standard PHP Warnings/Notices into Log Entries
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $this->logger->warning("PHP Notice/Warning: {$message}", [
                'file' => $file,
                'line' => $line
            ]);
            return true;
        });
    }

    /**
     * Helper to log incoming HTTP requests
     */
    public function logRequest(string $uri, string $slug = '', string $target = '', array $params = []): void
    {
        $this->logger->info("Incoming Request", [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri'    => $uri,
            'slug'   => $slug,
            'target' => $target,
            'params' => $params,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }

    /**
     * Formats JSON error responses during fatal failures
     */
    private function respondWithError(string $type, string $message, string $file, int $line): void
    {
        if (!headers_sent()) {
            header("Content-Type: application/json", true, 500);
        }

        echo json_encode([
            'error'   => $type,
            'message' => $message,
            'file'    => basename($file),
            'line'    => $line
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        exit(1);
    }
}