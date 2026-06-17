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


    /**
     * Default Neural Stack for Thomasons V3.
     * Can be overridden in child controllers.
     */
    protected const REQUIRED_MODELS = [
        'llama3.1:latest',
        'nomic-embed-text:latest',
        // 'phi3:latest',
        // 'all-minilm:latest'
    ];

    public function __construct()
    {
        $this->loc      = new \App\Services\Location();
        $this->location = new \App\Services\Location();
        $this->smarty   = new \App\Services\Smarty();
        $this->logger   = new \App\Services\Logger();
        $this->session  = \App\Services\Session::getInstance();
        // Safety check: if DB isn't in GLOBALS, we force a grounded instantiation
        if (!$this->db) {
            $this->db = new \App\Services\Db(get_env(), $this->logger);
        }        

    }


    /**
     * Centralized File Path Resolver for Ingestion Handshakes.
     * Diagnostic mode: Tracks file metadata layout configurations in app.log.
     */
    protected function baseUpload(string $filename = ''): array
    {
        $cleanName = $filename ? basename($filename) : 'EMPTY';

        // Log the structural validation check cleanly using your standard trace format
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

        // Return empty structure for now to keep child signatures happy without altering disk state
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

    /**
     * Helper for quick variable dumping.
     */
    public function dBug($debug){
        echo "<pre>";
        print_r($debug);
        echo "</pre>";
    }

   public function now(){
    return date('Y-m-d h:m:s');
   }

  /**
  * Request method
  **/
  public function old_request($post){

   // 1. If $post is empty, try to get the raw body
    if (empty($post)) {
        $post = file_get_contents('php://input');
    }

    // 2. Debugging: Log what is actually arriving
    if (empty($post)) {
        $this->json(['error' => 'No data received']);
        return;
    }

    $data = array('id' => '');
    $conditions = json_decode($post, true);

    // 3. Ensure JSON decoded correctly
    if (!$conditions) {
        $this->json(['error' => 'Invalid JSON']);
        return;
    }
    return $conditions;

  }


    /**
     * Unified Request Handler
     * Place this in your BaseController
     */
    public function request($key = null) {
        // 1. Check for JSON input
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);

        // 2. If valid JSON, use it; otherwise, fall back to $_REQUEST
        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) 
                ? $decoded 
                : $_REQUEST;

        // 3. Return specific key or entire dataset
        if ($key) {
            return $data[$key] ?? null;
        }
        return $data;
    }

    /**
     * Unified Service Gateway
     * Supports GET/POST and dynamic URL routing.
     */
    public function respond($payload = null, string $url = null, string $method = 'POST') 
    {
        $targetUrl = $url ?? self::RAG_SERVICE_URL; // Fallback to class const if no URL provided
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
        $error = curl_error($ch);
        curl_close($ch);

        return ($httpCode === 200) ? $response : false;
    }

}// end of class
