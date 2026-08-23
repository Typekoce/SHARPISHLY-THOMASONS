You hit the nail on the head. Perplexity's unit tests caught the exact flaws in that previous refactoring attempt. By stripping methods out of `BaseController.php` to "clean it up," critical contract methods that child controllers and long-running background tasks depend on were inadvertently omitted. That broke backwards compatibility and created missing method exceptions across the system. Furthermore, relying on `$this->db` instantiation inside `BaseController` introduced redundant PDO connection allocations on every controller execution, completely ignoring the global service initialization provided during bootstrap.

Below is the **uncut, comprehensive technical document** that fully maps out how every controller and service in Sharpishly operates, using native architecture without omitting functionality or over-engineering.

---

# SHARPISHLY-THOMASONS V3: SYSTEM ARCHITECTURE & TECHNICAL AUDIT

## 1. Executive System Overview & Data Flow

Sharpishly-Thomasons V3 is built on a hybrid "Brain/Cognition" architecture designed for high-performance, local RAG (Retrieval-Augmented Generation) and vector inference.

* **The Brain (PHP 8.2+ MVC):** Handles HTTP request parsing, routing, transactional business logic, ORM query abstractions, and view generation. Runs natively without local Composer or third-party PHP libraries.


* **The Cognition Layer (Python / LangChain / Ollama):** Handles document ingestion, vector embeddings (`nomic-embed-text`, `jina-v2`), RAG inference (`llama3`), and GraphRAG operations via synchronous HTTP REST APIs.


* **The Skin (Vanilla JS SPA):** Front-end UI constructed using pure DOM APIs (`document.createElement`) to guarantee security against XSS without external UI frameworks.


* **Durable Storage & Database:** MariaDB 10.11 manages relational metadata, job state, and audit logs. Disk storage (`storage/`) acts as a durable staging buffer for file ingestion, intermediate pipeline artifacts, and ephemeral runtime logs.



```text
[ Client Browser ] (Vanilla JS / Pure DOM)
        │
        │ Synchronous HTTP / JSON
        ▼
[ web/php/src/index.php ] ──► [ bootstrap.php ] (Autoloader & Security Gatekeeper)
        │
        ▼
[ Action Controller ] ── (Extends BaseController)
   ├── ORM Execution ──────► [ App\Services\Orm ] ──► [ MariaDB 10.11 ]
   ├── Diagnostic Check ───► [ BaseController::runDiagnosticScript() ]
   └── Neural Handshake ───► [ cURL Multi / REST ] ──► [ Python AI / Ollama Engine ]

```

---

## 2. Core Framework Blueprint & Uncut Engine Code

### A. Environment Bootstrapping (`web/php/src/bootstrap.php`)

Guarantees clean, single-pass environment loading, PSR-4 autoloading, exception trapping, and security initialization without circular `env.php` requires.

```php
<?php
declare(strict_types=1);

/**
 * SHARPISHLY BOOTSTRAP
 * Encapsulated initialization for Web UI, Security, and Migrations.
 */

define('PROJECT_ROOT', dirname(__DIR__, 3));

/**
 * 1. Environment Loader
 */
function initializeEnvironment(string $root): void {
    $path = $root . '/env.php';
    
    if (!file_exists($path)) {
        error_log("Bootstrap Error: env.php not found at $path");
        return;
    }

    require_once $path;
}

/**
 * 2. PSR-4 Autoloader
 */
function initializeAutoloader(string $baseDir): void {
    spl_autoload_register(function ($class) use ($baseDir) {
        $prefix = 'App\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/**
 * Load environment constants and return as a configuration array.
 */
function get_env(): array {
    return [
        'db_name' => defined('DB_NAME') ? DB_NAME : null,
        'db_user' => defined('DB_USER') ? DB_USER : null,
        'db_pass' => defined('DB_PASS') ? DB_PASS : null,
        'db_host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
        'app_dev' => defined('APP_DEV') ? APP_DEV : 'production',
    ];
}

/**
 * 3. Security Gatekeeper Initialization
 */
function initializeGatekeeper(): void {
    if (defined('DEBUG') && DEBUG === true) {
        \App\Security\Security::applyHeaders();
        \App\Security\Session::getInstance();
    }

    $GLOBALS['security'] = [
        'monitor'    => new \App\Security\MonitorNetworkTraffic(),
        'incident'   => new \App\Security\IncidentResponse(),
        'vuln'       => new \App\Security\VulnerabilityManager(),
        'protection' => new \App\Security\DataProtection(),
        'compliance' => new \App\Security\ComplianceEngine(),
        'policy'     => new \App\Security\PolicyManager()
    ];
}

/**
 * 4. Database & Logger Factory
 */
function initializeServices(): void {
    $logger = new \App\Services\Logger();
    $GLOBALS['logger'] = $logger;

    initializeGatekeeper();
    
    $logger->info("Initializing Database connection...");
    $config = get_env();
    $GLOBALS['db'] = new \App\Services\Db($config, $logger);
}

/**
 * EXECUTION & EXCEPTION HANDLER
 */
set_exception_handler(function (\Throwable $e) {
    $msg = "Bootstrap Fatal: " . $e->getMessage();
    
    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->error($msg);
    } else {
        error_log($msg);
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Bootstrap Error: ' . $e->getMessage(),
        'trace'   => $e->getFile() . ' on line ' . $e->getLine()
    ]);
    exit;
});

initializeEnvironment(PROJECT_ROOT);
initializeAutoloader(__DIR__ . '/');
initializeServices();

```

---

### B. Complete Uncut Controller Core (`App\Controllers\BaseController`)

Preserves every single operational method (`getNeuralStatus`, `curlRequest`, `runDiagnosticScript`, `baseUpload`, `request`, `respond`, `render`, `json`, timestamps) while fixing property duplication (`$location` vs `$loc`) and leveraging existing global database connections safely.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Db;
use App\Services\Location;
use App\Services\Smarty;
use App\Services\Logger;
use App\Services\Session;
use App\Services\PromptService;
use App\Services\Orm;
use App\Services\Diagnostics;
use Throwable;

abstract class BaseController
{
    protected ?Db $db = null;
    protected Location $loc;
    protected Smarty $smarty;
    public Logger $logger;
    public Session $session;
    public PromptService $prompt;
    public Orm $orm;
    public Diagnostics $diagnostics;

    protected const RAG_SERVICE_URL = 'http://localhost:8765/rag/ask';

    protected const REQUIRED_MODELS = [
        'llama3:latest',
        'nomic-embed-text:latest',
        'jina/jina-embeddings-v2-small-en:latest'
    ];

    public function __construct()
    {
        $this->logger      = $GLOBALS['logger'] ?? new Logger();
        $this->diagnostics = new Diagnostics($this->logger);
        $this->loc         = new Location();
        $this->smarty      = new Smarty();
        $this->session     = Session::getInstance();
        $this->prompt      = new PromptService();
        $this->orm         = new Orm();

        // Safely reuse global Database singleton or instantiate if missing
        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof Db) {
            $this->db = $GLOBALS['db'];
        } else {
            $this->db = new Db(get_env(), $this->logger);
        }
    }

    /**
     * Safely executes diagnostic shell scripts.
     */
    protected function runDiagnosticScript(string $scriptName): array 
    {
        $allowed = ['rag_check.sh', 'worker_check.sh', 'ollama_check.sh'];
        
        if (!in_array($scriptName, $allowed, true)) {
            return ['status' => 'error', 'message' => 'Unauthorized script execution attempt.'];
        }

        $scriptPath = $this->loc->baseDir() . "pymvc/app/scripts/" . $scriptName;
        $output = shell_exec("bash " . escapeshellarg($scriptPath));
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
     */
    protected function baseUpload(string $filename = ''): array
    {
        $cleanName = $filename ? basename($filename) : 'EMPTY';
        $this->logger->log("NP Base Handshake: Path resolution diagnostic triggered for file [{$cleanName}]", 'INFO');

        return [
            'upload_dir'      => $this->loc->uploads(),
            'target_file'     => $this->loc->uploads() . '/' . $cleanName,
            'nats_ingest_dir' => $this->loc->storage() . '/ingest',
            'filename'        => $filename ? $cleanName : null,
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
     * Interrogates the Ollama engine.
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
     * Orchestrates Header, Main, and Footer views using Smarty.
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
     * Loads a view file and processes it via Smarty.
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

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function timestamp(): string
    {
        return $this->now();
    }

    /**
     * Unified Request Handler
     */
    public function request($key = null) 
    {
        $postData = json_encode($_POST);
        $getData  = json_encode($_GET);

        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);

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

```

---

## 3. Comprehensive Controller Domain Audit

Here is the technical operational breakdown across all core controller domains in the system:

### Domain 1: Ingestion & Document Pipelines

* **`FileController.php`:** Receives multi-part CSV uploads (`$_FILES['csv_data']`), sanitizes line endings and control characters, saves the target file to `$this->loc->uploads()`, and creates a tracking record in the `jobs` database table.


* **`IngestionController.php`:** Manages remote HTML document fetching and cleaning. Strips `<script>`, `<style>`, and SVG tags prior to writing raw and cleaned snapshot files to storage. Also provides asynchronous queueing by generating executable shell scripts in `storage/cmd/jobs/waiting/`.


* **`IndeedApiController.php`:** Scans HTML/RSS snapshots from the storage directory. Uses `DOMDocument` and `DOMXPath` to parse local job postings without third-party web scrapers.



### Domain 2: AI Execution, RAG & Vector Handshakes

* **`AgentExecuteController.php`:** Orchestrates asynchronous background jobs. Extracts payload conditions, executes DB transactions through `Orm::execute()`, records query execution traces in MariaDB, and returns structured processing status.


* **`ChatController.php`:** Accepts user prompts, assigns a tracking ID (`uniqid('chat_')`), and stages JSON payload contracts to the vector storage directory (`$this->loc->vectorStorage()`) for Python worker processing.


* **`CvController.php`:** Handles CV tailoring workflows. Encodes binary CV documents to base64, packages vacancy context into a structured RAG task payload (`tailor_cv`), dispatches the request to the cognition service via `$this->respond()`, and records audit logs using `CvAuditModel`.


* **`EventAgentController.php`:** Coordinates EventBrite platform data syncing, routes entity parsing through `PromptService`, verifies Ollama model readiness via `$this->getNeuralStatus()`, and returns health/sync statuses.



### Domain 3: Cloud, Identity & Integration Proxies

* **`ApiAiServicesController.php`:** Unified client proxy connecting 13 LLM/Cloud providers (AWS, Azure, ChatGPT, Claude, Gemini, Grok, DeepSeek, Ollama, Cohere). Resolves template paths, enforces headers (e.g., Anthropic versioning), and handles JSON serialization.


* **`AzureapiController.php` & `GoogleapiController.php`:** Handle OAuth2 authorization code exchanges, user profile retrieval (`/v1.0/me`, `/oauth2/v3/userinfo`), and file-system token caching (`0700` restricted directory permissions).


* **`DhillonsController.php`:** Operational venue gateway integrating Square, OpenTable, Eventbrite, and ClickUp. Features parallel cURL dispatching (`$this->orm->executeParallel()`) and Ollama synthesis.



### Domain 4: Infrastructure, Security & System Health

* **`HealthController.php`:** Runs shallow and deep diagnostics. Evaluates MariaDB connectivity, checks local Python RAG endpoints (`http://localhost:8765/health`), triggers diagnostic scripts (`rag_check.sh`, `worker_check.sh`, `ollama_check.sh`), and interrogates the Ollama neural stack.


* **`PentestDiagnosticsController.php`:** Aggregates threat reports, cross-references active shell execution jobs, and parses security logs from durable storage.


* **`InfrastructureController.php`:** Delegates system patching, evaluates security compliance frameworks (GDPR, ISO27001), and tracks virtualization resource usage.



---

## 4. Key Architectural Trade-Offs & Best Practices

1. **Explicit Singletons over "God Object" Registries:** Removing the Registry pattern required explicit service initialization in `BaseController`. By checking `$GLOBALS['db']` before instantiating a new PDO instance, connection pooling is maintained across child controllers without re-introducing dependency black holes.


2. **Dynamic Storage Resolution:** Hardcoded paths like `/var/www/html` or `/home/user/` are banned. All file operations route through `$this->loc` methods (e.g., `$this->loc->uploads()`, `$this->loc->storage()`) to ensure cross-environment portability across local dev and production.


3. **No Raw SQL:** Models and controllers strictly assemble condition arrays for `Orm::execute()`. This guarantees that all queries utilize prepared PDO statements with bound parameters, insulating the application from SQL injection attacks.
