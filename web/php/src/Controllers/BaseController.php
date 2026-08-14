<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * THOMASONS V3 – BaseController
 * Centralized service retrieval and Neural Handshake orchestration.
 */
use App\Services\Db;
use App\Services\Location;
use App\Services\Smarty;
use App\Services\Logger;
use App\Services\Session;
use App\Services\PromptService;
use App\Services\Orm;
use Throwable;

abstract class BaseController
{
    protected $db;
    protected $loc;
    protected $location;
    protected $smarty;
    public $logger;
    protected $model;
    public $session;
    public $prompt;
    public $orm;

    protected const RAG_SERVICE_URL = 'http://localhost:8765/rag/ask';

    /**
     * Default Neural Stack for Thomasons V3.
     * Can be overridden in child controllers.
     */
    protected const REQUIRED_MODELS = [
        'llama3:latest',
        'nomic-embed-text:latest',
        'jina/jina-embeddings-v2-small-en:latest'
        // 'phi3:latest',
        // 'all-minilm:latest'
    ];

    public function __construct()
    {
        $this->loc      = new Location();
        $this->location = new Location();
        $this->smarty   = new Smarty();
        $this->logger   = new Logger();
        $this->session  = Session::getInstance();
        $this->prompt   = new PromptService();
        $this->orm      = new Orm();

        // Safety check: if DB isn't in GLOBALS, force a grounded instantiation
        if (!$this->db) {
            $this->db = new Db(get_env(), $this->logger);
        }        
    }

    /**
     * Safely executes diagnostic shell scripts.
     * @param string $scriptName The filename in the scripts/ directory.
     * @return array A standardized JSON response.
     */
    protected function runDiagnosticScript(string $scriptName): array 
    {
        // 1. Strict Whitelist: Only permit specific, pre-vetted scripts.
        $allowed = ['rag_check.sh', 'worker_check.sh', 'ollama_check.sh'];
        
        if (!in_array($scriptName, $allowed, true)) {
            return ['status' => 'error', 'message' => 'Unauthorized script execution attempt.'];
        }

        // 2. Path Construction: Base directory enforced.
        $scriptPath = "/home/seaview/Documents/SHARPISHLY-THOMASONS/pymvc/app/scripts/" . $scriptName;
        
        // 3. Execution: Use escapeshellarg to prevent injection, capture stdout.
        $output = shell_exec("bash " . escapeshellarg($scriptPath));
        
        // 4. Parsing: Decode JSON or return a standard failure object.
        $data = json_decode($output ?? '', true);
        
        if (is_array($data)) {
            return $data;
        }

        return [
            'status' => 'error', 
            'message' => 'Diagnostic script returned malformed or empty output.'
        ];
    }

    /**
     * Centralized File Path Resolver for Ingestion Handshakes.
     * Diagnostic mode: Tracks file metadata layout configurations in app.log.
     */
    protected function baseUpload(string $filename = ''): array
    {
        $cleanName = $filename ? basename($filename) : 'EMPTY';

        // Log the structural validation check cleanly using standard trace format
        $this->logger->log("NP Base Handshake: Path resolution diagnostic triggered for file [{$cleanName}]", 'INFO');

        return [
            'upload_dir'      => '',
            'target_file'     => '',
            'nats_ingest_dir' => '',
            'filename'        => $filename ? $cleanName : null,
        ];
    }

    /**
     * Centralized File Path Resolver for Ingestion Handshakes.
     * Currently operating in strict logging-only diagnostics mode.
     */
    protected function old_baseUpload(string $filename = ''): array
    {
        // Log the structural event tracking line cleanly
        $this->logger->log("NP Base Handshake: Resolving path configurations for file: " . ($filename ?: 'EMPTY'), 'INFO');

        // Return empty structure to keep child signatures happy without altering disk state
        return [
            'upload_dir'      => '',
            'target_file'     => '',
            'nats_ingest_dir' => '',
            'filename'        => $filename ? basename($filename) : null,
        ];
    }   

    /**
     * Standardized JSON Response Handler
     */
    protected function json(array $data, int $code = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($code);
        }
        echo json_encode($data);
        exit;
    }

    /**
     * NEURAL HANDSHAKE: Interrogates the Ollama engine.
     * Centralized here so any controller can verify model "Brain Matter" existence.
     */
    protected function getNeuralStatus(): array
    {
        $status = [
            'active' => false, 
            'synced' => false, 
            'models' => []
        ];
        
        try {
            $ch = curl_init('http://llm:11434/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 1
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $response) {
                $status['active'] = true;
                $data = json_decode($response, true);
                $installed = $data['models'] ?? [];

                $allReady = true;
                foreach (static::REQUIRED_MODELS as $required) {
                    $match = null;
                    foreach ($installed as $m) {
                        if ($m['name'] === $required) {
                            $match = $m;
                            break;
                        }
                    }

                    if ($match && ($match['size'] > 0)) {
                        $gbSize = round($match['size'] / (1024 * 1024 * 1024), 2);
                        $status['models'][$required] = [
                            'size'     => $gbSize . " GB",
                            'progress' => '100%',
                            'state'    => 'Ready'
                        ];
                    } else {
                        $allReady = false;
                        $status['models'][$required] = [
                            'size'     => "0 GB",
                            'progress' => "0% (Missing)",
                            'state'    => 'Critical'
                        ];
                    }
                }
                $status['synced'] = $allReady;
            }
        } catch (Throwable $e) {
            $this->logger->error("Neural Handshake Failed: " . $e->getMessage());
            $status['error'] = "Ollama Offline";
        }
        
        return $status;
    }

    /**
     * Orchestrates Header, Main, and Footer views using the Smarty engine.
     */
    protected function render(array $data, array $views): void
    {
        $output = '';
        foreach ($views as $name => $path) {
            $output .= $this->renderView($path, $data);
        }
        echo $output;
    }

    /**
     * Loads a view file and processes it via the Smarty service.
     */
    protected function renderView(string $path, array $data): string
    {
        $viewPath = $this->loc->baseDir() . "php/views/{$path}.html";
        
        if (!file_exists($viewPath)) {
            $this->logger->error("View not found: " . $viewPath);
            return "";
        }

        $template = file_get_contents($viewPath);
        return $this->smarty->render($template, $data);
    }


    public function now()
    {
        return date('Y-m-d h:m:s');
    }

    public function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Legacy Request method
     */
    public function old_request($post)
    {
        if (empty($post)) {
            $post = file_get_contents('php://input');
        }

        if (empty($post)) {
            $this->json(['error' => 'No data received']);
            return;
        }

        $conditions = json_decode($post, true);

        if (!$conditions) {
            $this->json(['error' => 'Invalid JSON']);
            return;
        }
        return $conditions;
    }

    /**
     * Unified Request Handler
     * Parses JSON or superglobals safely without restricting to a mandatory 'query' key.
     */
    public function request($key = null) 
    {
        $postData = json_encode($_POST);
        $getData  = json_encode($_GET);

        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);

        // Standardized data extraction: prefer decoded JSON if valid array, otherwise $_REQUEST
        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) 
                ? $decoded 
                : $_REQUEST;

        $logMsg = "POST REQUEST METHOD: " . $postData . 
                  " | GET REQUEST METHOD: " . $getData . 
                  " | php://input: " . $raw . 
                  " | DATA: " . json_encode($data);

        $this->logger->log($logMsg, 'INFO');

        if ($key !== null) {
            return $data[$key] ?? null;
        }

        return $data;
    }

    public function respond($payload = null, string $url = null, string $method = 'POST') 
    {
        $targetUrl = $url ?? self::RAG_SERVICE_URL;
        $ch = curl_init($targetUrl);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !empty($error)) {
            $this->logger->log("Respond Error: HTTP $httpCode | CURL Error: $error", 'ERROR');
        }

        return ($httpCode === 200) ? $response : false;
    }

    /**
     * Reusable parallel HTTP fetch helper using native cURL multi handling.
     */
    public function curlRequest(array $endpoints): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($endpoints as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        $running = null;
        do {
            $mrc = curl_multi_exec($mh, $running);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($running > 0 && $mrc === CURLM_OK) {
            if (curl_multi_select($mh, 0.2) !== -1) {
                do {
                    $mrc = curl_multi_exec($mh, $running);
                } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            }
        }

        $results = [];
        foreach ($handles as $key => $ch) {
            $raw = curl_multi_getcontent($ch);
            $decoded = json_decode((string)$raw, true);

            $results[$key] = is_array($decoded) ? $decoded : ['raw' => trim((string)$raw)];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $results;
    }
}