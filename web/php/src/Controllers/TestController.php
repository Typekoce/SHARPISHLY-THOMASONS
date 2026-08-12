<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Orm;
use Throwable;

class TestController extends BaseController
{
    /**
     * Fast system health endpoint: /php/test/health
     */
    public function health(): void
    {
        $data = [
            'status'        => 'ok',
            'class'         => __CLASS__,
            'function'      => __FUNCTION__,
            'google_api'    => $this->decodeJsonRequest('googleapi'),
            'hotmail_api'   => $this->decodeJsonRequest('hotmailapi'),
            'azure_api'     => $this->decodeJsonRequest('azureapi'),
            'aws_api'       => $this->decodeJsonRequest('awsapi'),
            'process_check' => $this->safeShellExec(
                'ps aux | grep -E "ollama|rag_service" | grep -v grep'
            ),
            'ollama'        => $this->safeOllamaTags(),
            'RAG'           => $this->safeHttpHealth('http://127.0.0.1:8000/health'),
            'recent_work'   => [
                'ssl_setup'     => 'setup-local-ssl.sh',
                'installer'     => 'build-installer.sh',
                'controllers'   => [
                    'MobileAgentController.php' => 'Active',
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
                'logs'          => $this->safeShellExec('tail -n 20 storage/logs/*.log 2>/dev/null'),
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
     * Decode JSON request from internal endpoint.
     */
    private function decodeJsonRequest(string $path): ?array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 3,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $url = 'https://127.0.0.1/php/' . $path;
            $raw = @file_get_contents($url, false, $context);
            if ($raw === false) {
                return null;
            }

            return json_decode($raw, true);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Run ORM/LLM diagnostics.
     */
    private function runOrmDiagnostics(): array
    {
        $orm = new Orm();
        $rs  = [];

        // 1. ChatGPT call
        try {
            $response = $orm->execute([
                'source'  => 'ChatGPT',
                'action'  => 'create',
                'api_key' => 'YOUR_API_KEY',
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
            $response = $orm->execute([
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

        return $rs;
    }

    /**
     * Safe shell execution wrapper.
     */
    private function safeShellExec(string $cmd): ?string
    {
        $out = @shell_exec($cmd);
        return $out === false ? null : trim((string)$out);
    }

    /**
     * Safe Ollama tags fetch.
     */
    private function safeOllamaTags(): ?array
    {
        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 3,
                ],
            ]);

            $raw = @file_get_contents('http://127.0.0.1:11434/api/tags', false, $ctx);
            if ($raw === false) {
                return null;
            }

            return json_decode($raw, true);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Safe HTTP health check.
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