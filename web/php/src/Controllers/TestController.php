<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

/**
 * TestController – System health & ORM diagnostics.
 *
 * Routes:
 * - GET /php/test/health -> System health snapshot with aggregated status.
 * - GET /php/test/llm    -> Target ORM & Neural LLM diagnostics.
 * - GET /php/test/test   -> Legacy alias routing to health().
 */
class TestController extends BaseController
{
    /**
     * Fast system health endpoint: /php/test/health
     */
    public function health(): void
    {
        if (!defined('PROJECT_ROOT')) {
            $this->json(['status' => 'error', 'message' => 'PROJECT_ROOT constant is undefined.'], 500);
            return;
        }

        // Parallel multi-cURL execution
        $endpoints = [
            'google_api'        => 'http://127.0.0.1/php/googleapi',
            'hotmail_api'       => 'http://127.0.0.1/php/hotmailapi',
            'azure-hello-world' => 'http://127.0.0.1/php/azure/hello',
            'aws-hello'         => 'http://127.0.0.1/php/aws/hello',
        ];

        $subResults = $this->curlRequest($endpoints);

        $google     = $this->normalizeSubResult($subResults['google_api'] ?? null);
        $hotmail    = $this->normalizeSubResult($subResults['hotmail_api'] ?? null);
        $azureHello = $this->normalizeSubResult($subResults['azure-hello-world'] ?? null);
        $aws        = $this->normalizeSubResult($subResults['aws-hello'] ?? null);

        // Compute overall cluster health
        $subServices = [
            'google_api'        => $google,
            'hotmail_api'       => $hotmail,
            'azure-hello-world' => $azureHello,
            'aws-hello'         => $aws,
        ];

        $okCount = 0;
        foreach ($subServices as $service) {
            $status = $service['status'] ?? '';
            if ($status === 'ok' || $status === 'success' || ($service['statusCode'] ?? 0) === 200) {
                $okCount++;
            }
        }

        $logPath = escapeshellarg(PROJECT_ROOT . '/storage/logs/*.log');

        $data = [
            'server'            => $_SERVER,
            'status'            => ($okCount === count($subServices)) ? 'healthy' : 'degraded',
            'status_detail'     => [
                'ok_count'      => $okCount,
                'total_checks'  => count($subServices),
            ],
            'class'             => __CLASS__,
            'function'          => __FUNCTION__,
            'google_api'        => $google,
            'hotmail_api'       => $hotmail,
            'azure-hello-world' => $azureHello,
            'aws-hello'         => $aws,
            'process_check'     => $this->safeShellExec(
                'ps aux | grep -E "ollama|rag_service" | grep -v grep'
            ),
            'ollama'            => $this->getNeuralStatus(),
            'RAG'               => $this->safeHttpHealth('http://127.0.0.1:8000/health'),
            'recent_work'       => [
                'ssl_setup'     => 'setup-local-ssl.sh',
                'installer'     => 'build-installer.sh',
                'controllers'   => [
                    'MobileAgentController.php' => 'Completed',
                    'AwsController.php'          => 'active',
                    'AzureController.php'        => 'active',
                    'AzureFoundryController.php' => 'Pending',
                    'BaseCloudController.php'   => 'Pending',
                    'GoogleapiController.php'   => 'Pending',
                    'HotmailapiController.php'  => 'Pending',
                ],
                'services'      => ['Orm.php'],
                'documentation' => [
                    'docs/CONTRIBUTORS.md',
                    'todo.md',
                ],
                'logs'          => $this->safeShellExec("tail -n 20 {$logPath} 2>/dev/null"),
            ],
        ];

        $this->json($data);
    }

    /**
     * Dedicated ORM / LLM diagnostic endpoint: /php/test/llm
     */
    public function llm(): void
    {
        $data = [
            'class'    => __CLASS__,
            'function' => __FUNCTION__,
            'orm'      => $this->runOrmDiagnostics(),
        ];

        $this->json($data);
    }

    /**
     * Legacy catch-all for backwards compatibility: /php/test/test
     */
    public function test(string $id = ''): void
    {
        $this->health();
    }

    /**
     * Guarantees all sub-endpoint execution responses return as standardized arrays.
     */
    private function normalizeSubResult(mixed $result): array
    {
        if (is_array($result)) {
            if (isset($result['raw']) && is_string($result['raw'])) {
                $decoded = json_decode($result['raw'], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return $result;
        }

        if (is_string($result)) {
            $decoded = json_decode($result, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'status' => 'unreachable',
            'raw'    => $result,
        ];
    }

    /**
     * Run ORM/LLM diagnostics using inherited $this->orm and environment secrets.
     */
    private function runOrmDiagnostics(): array
    {
        $rs = [];

        // 1. ChatGPT call
        try {
            $response = $this->orm->execute([
                'source'  => 'ChatGPT',
                'action'  => 'create',
                'api_key' => getenv('OPENAI_API_KEY') ?: 'YOUR_API_KEY',
                'data'    => [
                    'model'    => 'gpt-4o',
                    'messages' => [['role' => 'user', 'content' => 'Hello!']],
                ],
            ]);
            $rs['ChatGPT'] = $response;
        } catch (Throwable $e) {
            $rs['ChatGPT'] = ['error' => $e->getMessage()];
        }

        // 2. Ollama call
        try {
            $response = $this->orm->execute([
                'source' => 'Ollama',
                'action' => 'create',
                'data'   => [
                    'model'  => 'llama3',
                    'prompt' => 'Explain MVC in one sentence',
                ],
            ]);
            $rs['Ollama'] = $response;
        } catch (Throwable $e) {
            $rs['Ollama'] = ['error' => $e->getMessage()];
        }

        // 3. AzureHelloWorld call
        try {
            $response = $this->orm->execute([
                'source' => 'AzureHelloWorld',
                'action' => 'read',
            ]);
            $rs['AzureHelloWorld'] = $response;
        } catch (Throwable $e) {
            $rs['AzureHelloWorld'] = ['error' => $e->getMessage()];
        }

        return $rs;
    }

    /**
     * Safe shell execution wrapper with max buffer trimming.
     */
    private function safeShellExec(string $cmd, int $maxBytes = 8192): ?string
    {
        $out = @shell_exec($cmd);
        if ($out === false || $out === null) {
            return null;
        }
        $trimmed = trim((string)$out);
        return mb_substr($trimmed, 0, $maxBytes);
    }

    /**
     * Safe HTTP health check stream fetch.
     */
    private function safeHttpHealth(string $url): ?string
    {
        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 3,
                ],
            ]);

            $raw = @file_get_contents($url, false, $ctx);
            return $raw === false ? null : trim((string)$raw);
        } catch (Throwable $e) {
            return null;
        }
    }
}