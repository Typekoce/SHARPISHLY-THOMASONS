This is the definitive update to the **CONTEXT.md**. I have stripped the "God Object" legacy and replaced it with a stark warning about the `Registry` failure to serve as a permanent guardrail for the remainder of this build.

---

### 🛠️ NEW ENVIRONMENT PROVISIONING & DEPLOYMENT

When provisioning the project on a new workstation or server environment, observe the following rules:

1. **Dynamic Path Resolution:** Never hardcode absolute user paths (e.g., `/home/username/`) in shell scripts or web configurations. All scripts (`14_post_install.sh`) must dynamically resolve `$ROOT_DIR` using relative path expansion (`dirname`).
2. **Directory Traversal & Permissions:** Nginx requires execute (`755`) permissions on all parent directories up to the root folder. Standard web root files must be assigned `www-data` ownership with `755` (directories) and `644` (files). Storage directories require sticky/group write permissions (`2775`).
3. **Loopback Endpoint Resolution:** The `BaseController::curlRequest()` helper automatically bypasses SSL verification for local loopbacks (`127.0.0.1`, `localhost`, `192.168.x.x`) and follows redirects. Ensure local Nginx site configs resolve to standard loopback IPs.
4. **Environment Bootstrapping:** `env.php` must be loaded prior to `bootstrap.php`. Gatekeeper execution can be toggled inside `initializeServices()` for local dev isolation.

# PHP, Js, Python, Guidelines: 
  1. Do not remove critical functionality.
  2. Do not over-engineer solutions.
  3. Do not nest IDs.
  4. No inline HTML strings in JS—use pure DOM construction (`document.createElement`).

# SHARPISHLY-THOMASONS V3: PROJECT CONTEXT
**Current Version:** 3.1.0 (Post-Registry)
**Last Audit:** April 9, 2026

## 🎯 MISSION
A professional-grade, service-oriented architecture for a Neural Pipeline. Decoupled PHP "Brain" (MVC) and Python/LangChain "Cognition" layer for high-performance local vector inference.

## 🏗️ ARCHITECTURE & CONSTRAINTS
- **Zero External JS/PHP Libraries:** No Composer (locally), no NPM. Pure Vanilla JS and Native PHP.
- **Separation of Concerns:** - `/web/frontend`: The Skin (Vanilla JS SPA).
    - `/web/php/src`: The Brain (MVC, PSR-style Autoloader).
    - `/ai` & `/llm`: The Cognition (Python, Ollama, Qdrant).
- **Direct Service Injection:** **DEPRECATED REGISTRY PATTERN.** Controllers must directly instantiate required Services or use static Singletons where appropriate.
- **Centralized Storage:** Absolute root `/var/www/html/storage/` (mapped 1:1 to host `./storage`).
- **Data Integrity:** No Raw SQL. Strict use of PDO Prepared Statements within `App\Models`.

## 📂 DIRECTORY MAP
- `web/frontend/`: `index.html`, `script.js`, `styles.css`.
- `web/php/src/`: Entry point `index.php`, `bootstrap.php`.
- `storage/`: `uploads/`, `logs/`, `temp/`.
- `infra/`: Docker configurations (Nginx, MySQL, PHP, AI).

## ⚖️ DESIGN DECISIONS & POST-MORTEMS

### 🛑 THE REGISTRY "NIGHTMARE" (2026-03-23 to 2026-04-08)
The project suffered a **two-week total stagnation** due to the `App\Core\Registry` pattern. 
* **The Failure:** It acted as a single point of failure and a "black box" for dependency loops. Namespace shifts (App\Registry vs App\Core\Registry) caused a "Whack-a-Mole" error chain across the inheritance tree.
* **The Decision:** The Registry has been **purged**. Its removal is a "Settled Law" of the architecture. 
* **The Lesson:** Avoid "God Objects" in low-resource environments. Explicit dependency instantiation provides clearer stack traces and faster debugging.

### 🛡️ INFRASTRUCTURE-FIRST POLICY
- **Fail Fast:** Removed silent fallbacks (like `DbJson`). If the MySQL connection fails, the app must crash immediately to prevent "weird" secondary errors.
- **Permission Scope:** All processes must stay within `www-data` (UID 33).
- **Logging:** Ephemeral `stderr` logging only. Do not rely on persistent log files for real-time debugging.

## 🧠 NEURAL PIPELINE STATUS
1. **Upload:** Managed by `UploadController`.
2. **Handshake:** PHP triggers Python `/process` endpoint.
3. **Embedding:** Python handles Nomic/MiniLM vectors.
4. **Storage:** Vectors pushed to Qdrant; metadata synced to MySQL.

## 🚦 SYSTEM STATUS (2026-04-09)
- **Infrastructure:** Recovered from `DISK_FULL` and `Inaccessible` VM states.
- **Memory:** VM RAM increased; VPS Swap/ZRAM pending verification.
- **Database:** `pdo_mysql` confirmed.
- **Task:** Implement `ProvisionController.php` to audit environment health (Docker, Ollama, Models).

---

### 🚦 The "First Strike" of the Day
Now that the context is accurate and the "Registry Ghost" is exorcised:

1.  **Find your VM IP:** Use that `ip addr show | grep inet` we discussed.
2.  **Verify SSH:** Get back into the Tardis terminal.
3.  **Start the Build:** Run `make clean` (the new scorched-earth version) then `make up`.

**What is the IP address? I need to know if we're on a Bridged or NAT network to predict our next SSH hurdle.**

### 📁 DURABLE STORAGE & ASYNC WORKER ARCHITECTURE
- **`storage/` Staging Buffer:** Serves as the durable landing zone for RAG document ingestion (PDFs, raw datasets) prior to text extraction, chunking, and vector database insertion.
- **Async Isolation:** Long-running compute (vectorization) and rate-limited side-effects (Email, SMS, queued background tasks) belong in asynchronous queues serviced by dedicated workers.
- **Web Lifecycle Boundary:** Keeps HTTP API endpoints thin, fast, and strictly synchronous, avoiding PHP timeouts and memory spikes.

# Code Conventions
* All PHP Controllers inherit from the BaseController
* All paths should use $this->loc()
* Do not omit critical code
* Do not over-engineer
* All Models inherit for BaseModel
* All routes are handled by index.php
* All autoloading handled by bootstrap.php


# API request using Orm.php
```
<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

class AwsController extends BaseController
{
    public function hello(string $id = ''): void
    {
        $userInput   = $this->request('user') ?? 'Paul';
        $actionInput = $this->request('action') ?? 'test';

        $data = [
            'id' => $id,
        ];

        try {
            $conditions = [
                'source' => 'AwsHelloWorld',
                'method' => 'POST',
                'data'   => [
                    'user'   => $userInput,
                    'action' => $actionInput,
                    'id'     => $id,
                ],
            ];

            $response = $this->orm->execute($conditions);

            $data[__FUNCTION__] = $response;
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Throwable $e) {
            $this->logger->error("AwsController Error: " . $e->getMessage());
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

# Autoloader bootstrap.php
```
<?php
declare(strict_types=1);

/**
 * SHARPISHLY BOOTSTRAP
 * Encapsulated initialization for Web UI and Migrations.
 */

define('PROJECT_ROOT', dirname(__DIR__, 3));

/**
 * 1. Environment Loader
 */
function initializeEnvironment(string $root): void {
    $path = $root . '/env.php';
    
    if (!file_exists($path)) {
        // We log a critical error because the app cannot function without this grounding.
        error_log("Bootstrap Error: env.php not found at $path");
        return;
    }

    // Since env.php uses define(), requiring it makes 
    // the constants globally available immediately.
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
    $file = PROJECT_ROOT . "/env.php";

    if (!file_exists($file)) {
        // We throw an exception here because the app cannot function without this grounding.
        throw new \Exception("Configuration Error: 'env.php' not found at " . $file);
    }

    require_once $file;

    // Return the constants as an array to keep service constructors clean
    return [
        'db_name' => defined('DB_NAME') ? DB_NAME : null,
        'db_user' => defined('DB_USER') ? DB_USER : null,
        'db_pass' => defined('DB_PASS') ? DB_PASS : null,
        'db_host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
        'app_dev' => defined('APP_DEV') ? APP_DEV : 'production',
    ];
}

/**
 * 3. Database & Logger Factory
 */
function initializeServices(): void {
    // Instantiate Logger first so it can be used by other services
    $logger = new \App\Services\Logger();
    $GLOBALS['logger'] = $logger;

    Gatekeeper();
    
    $logger->info("Initializing Database connection...");

    $config = get_env();

    $db = new \App\Services\Db($config, $logger);

}
/**
 * 3.1 Gatekeeper will be renamed GateKeeper after testing
 */
function Gatekeeper(){
    // Existing base security
    if(DEBUG == TRUE){
        \App\Security\Security::applyHeaders();
        $session = new \App\Security\Session();
    }

    // Register Security Services into the GLOBALS scope
    // This allows you to call them from any Controller without re-instantiating
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
 * EXECUTION PHASE
 */

set_exception_handler(function ($e) {
    $msg = "Bootstrap Fatal: " . $e->getMessage();
    
    // Check for global instance before falling back to system log
    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->error($msg);
    } else {
        error_log($msg);
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
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


# Routes index.php
```
<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Logger;

$logger = new Logger();

/**
 * 2. Define Route Aliases
 * Maps the URL slug to the [ControllerName, MethodName]
 */
$aliases = [
    'upload'               => ['Upload', 'index'],
    'job-status'           => ['Upload', 'status'],
    'search'               => ['Search', 'query'],
    'chat'                 => ['Chat', 'ask'],
    'pentest-scan'         => ['Pentest', 'scan'],
    'pentest-diagnostics'  => ['PentestDiagnostics', 'treats'],

    // OAuth & Cloud Service Callbacks
    'auth-google-callback'  => ['Auth', 'googleCallback'],
    'auth-hotmail-callback' => ['Auth', 'hotmailCallback'],
    'auth-aws-callback'     => ['Auth', 'awsCallback'],

    // Indeed API calls
    'indeed-api'            => ['IndeedApi', 'index'],
    'auth-indeed-callback'  => ['IndeedApi', 'callback'],
    'indeed-token'          => ['IndeedApi', 'fetchToken'],

    // Mobile Demo
    'mobile-agent'          => ['MobileAgent','index'],
    'mobile-agent-create'   => ['MobileAgent', 'create'],

    // Agent Workers
    'agent-execute'         => ['AgentExecute','start']
    
    // 'chat-stream'        => ['Chat', 'stream'],
];

// 3. Parse the URI (CLI-aware fallback)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

$parts = explode('/', trim($uriPath, '/'));
//echo "<pre>";
// 4. Strip prefixes (/php/ or /api/)
if (isset($parts[0]) && ($parts[0] === 'php' || $parts[0] === 'api')) {
    array_shift($parts);
}

// 5. Resolve the Route & Parameters
$slug = $parts[0] ?? 'home';
$params = [];

if (isset($aliases[$slug])) {
    [$controllerBase, $methodName] = $aliases[$slug];
    // If URI is /php/job-status/3, '3' becomes the first parameter
    $params = array_slice($parts, 1);
} else {
    $controllerBase = ucfirst($slug);
    $methodName     = $parts[1] ?? 'index';
    $params         = array_slice($parts, 2);
}
//TODO: Enable URL parameters

$className = "App\\Controllers\\{$controllerBase}Controller";

// 6. Execution
if (class_exists($className)) {
    // Instantiate the controller
    $controller = new $className();
    
    if (method_exists($controller, $methodName)) {
        
        $logger->info("Routing Request", [
            'controller' => $className,
            'method'     => $methodName,
            'params'     => $params
        ]);

        // Spread the parameters into the method
        // e.g., FileController->status('3')
        $controller->{$methodName}(...$params);

    } else {
        header("Content-Type: application/json", true, 404);
        echo json_encode(["error" => "Method '$methodName' not found in $className"]);
    }
} else {
    header("Content-Type: application/json", true, 404);
    echo json_encode(["error" => "Controller '$className' not found"]);
}

```


# Base Model
```
<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use App\Services\Logger;

class BaseModel {

    protected Db $db;

    public function __construct()
    {
        $logger = $GLOBALS['logger'] ?? new Logger();
        $config = get_env(); 
        $this->db = new Db($config, $logger);
    }

    // In BaseModel.php (The "Pipe")
    public function request(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    /**
     * Unified HTTP Execution for Social APIs
     */
    protected function http(string $url, ?string $token, array $params = [], string $method = 'GET', bool $isJson = true): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [];
        if ($token) $headers[] = "Authorization: Bearer $token";
        if ($isJson && $method === 'POST') $headers[] = "Content-Type: application/json";
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $isJson ? json_encode($params) : http_build_query($params));
        }

        $raw = curl_exec($ch);
        $res = json_decode($raw, true) ?? [];
        curl_close($ch);

        // Normalized response for Controllers
        return [
            'success' => !isset($res['error']) && !isset($res['error_code']),
            'data' => $res
        ];
    }
}
```

# BaseController
```
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
use App\Services\Diagnostics;
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
    public $diagnostics;

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
        $this->logger      = new Logger();
        $this->diagnostics = new Diagnostics($this->logger);
        $this->loc         = new Location();
        $this->location    = new Location();
        $this->smarty      = new Smarty();
        $this->session     = Session::getInstance();
        $this->prompt      = new PromptService();
        $this->orm         = new Orm();

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

        // 2. Path Construction: Base directory resolved dynamically via Location service.
        $scriptPath = $this->loc->baseDir() . "pymvc/app/scripts/" . $scriptName;
        
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
```

# PYMVC-V1
```
from urllib.parse import urlparse
from controllers.home_controller import HomeController
from controllers.about_controller import AboutController

class Router:
    def __init__(self):
        self.routes = {
            "/": (HomeController, "index"),
            "/about": (AboutController, "index")
        }

    def dispatch(self, url):
        path = urlparse(url).path
        if path in self.routes:
            cls, action = self.routes[path]
            return getattr(cls(), action)()
        return "404 Not Found"

class App:
    def run(self, request_url="/"):
        router = Router()
        return router.dispatch(request_url)

if __name__ == "__main__":
    app = App()
    print(app.run("/"))       # Output: Home Page Content
    print(app.run("/about"))  # Output: About Us Content

# About Controller
from models.about_model import AboutModel

class AboutController:
    def index(self):
        return AboutModel().get()

class AboutModel:
    def get(self):
        return "About Us Content"
```

# Description of functionality for Controllers
*
Here is the technical audit breakdown for each file, structured for inclusion in `docs/md/CONTEXT.md`.

---

### File Technical Breakdown

**`ApiAiServicesController.php`**

* **Namespace / Base Class:** `App\Controllers\ApiAiServicesController` extending `BaseApiController`.


* **Purpose:** Serves as a unified HTTP proxy client and endpoint router across 13 external cloud infrastructure providers and AI LLM service endpoints (AWS, Xero, Azure, AzureFoundry, ChatGPT, Claude, Gemini, Grok, Kimi, DeepSeek, Ollama, Mistral, Cohere).


* **Core Functionality:**
* Maps high-level abstract actions (`create`, `read`, `update`, `delete`) to standard HTTP verbs (`POST`, `GET`, `PUT`, `DELETE`).


* Resolves template placeholders (`{resource}`, `{model}`) and dynamic parameters within endpoint URLs.


* Manages provider-specific authentication headers (e.g., Anthropic versioning headers for Claude, Bearer tokens for standard REST, query parameters for Gemini).


* Executes synchronous external requests via cURL with payload JSON serialization and response decoding.





---

**`AgentController.php`**

* **Namespace / Base Class:** `App\Controllers\AgentController` extending `BaseController`.


* **Purpose:** Handles legacy agent listing and automated form submission tasks.


* **Core Functionality:**
* `index()`: Returns a JSON list of all agents ordered descending by ID using the internal database abstraction layer.


* `fillForm()`: Validates target URLs, retrieves developer details from the database, constructs a field mapping payload, and dispatches execution via shell command (`shell_exec`) to `pymvc/app/form_automation.py`.





---

**`AgentExecuteController.php`**

* **Namespace / Base Class:** `App\Controllers\AgentExecuteController` extending `BaseController`.


* **Purpose:** Manages asynchronous and direct job execution pipelines, routing query conditions through the ORM service and logging audit trails.


* **Core Functionality:**
* `start()`: Retrieves input via `job_id` or direct `payload` body.


* Reads pending job records from the database, updates job status to `processing`, and extracts execution conditions.


* Invokes `App\Services\Orm::execute()` to dispatch conditions through the framework database/service layer.


* Logs queries, agent IDs, raw execution conditions, and JSON responses to the `queries` database table.


* Updates job processing status (`completed` or `failed`) and returns JSON status.





---

**`AgenticController.php`**

* **Namespace / Base Class:** `App\Controllers\AgenticController` extending `BaseController`.


* **Purpose:** Ingests remote web page content for snapshot archiving and analysis.


* **Core Functionality:**
* `index()`: Captures a target URL (defaults to job search feeds), fetches raw HTML content, and records snapshots using `SnapshotsModel`.


* `getContents()`: Executes custom cURL requests with browser headers (Firefox user agent, security headers, compression encodings) to bypass basic anti-scraping filters.


* Writes snapshot metadata to snapshot registries and stores raw string contents with timestamping.





---

**`AutomationController.php`**

* **Namespace / Base Class:** `App\Controllers\AutomationController` extending `BaseController`.


* **Purpose:** Acts as an orchestration controller integrating external RPA processes, Microsoft Power Platform integrations, and compliance auditing.


* **Core Functionality:**
* Uses constructor dependency injection to load `RpaOrchestrationService`, `PowerPlatformService`, and `GovernanceService`.


* `executeRoboticTask()`: Validates compliance constraints before triggering RPA tasks.


* `deployPowerAppIntegration()`: Configures Power Platform service integrations.


* `generateProcessReport()`: Generates and returns process audit trail logs.

### File Technical Breakdown

**`AwsapiController.php`**

* **Namespace / Base Class:** `App\Controllers\AwsapiController` extending `BaseCloudController`.


* **Purpose:** Serves as a standalone mock identity endpoint aligning with framework conventions.


* **Core Functionality:**
* `index()`: Immediately outputs a static JSON response returning mock AWS Security Token Service (STS) caller identity metadata, including account ID, IAM user ARN (`paul.mcintosh`), username, and target region (`eu-west-2`).





---

**`AwsController.php`**

* **Namespace / Base Class:** `App\Controllers\AwsController` extending `BaseController`.


* **Purpose:** Dispatches AWS-bound test payloads through the framework's internal ORM service layer.


* **Core Functionality:**
* `hello()`: Extracts `user` and `action` parameters from incoming request inputs and constructs a structured condition array for the `AwsHelloWorld` source via HTTP `POST`.


* Passes execution conditions to `$this->orm->execute()`.


* Catches `Throwable` exceptions, logging failures via `$this->logger->error()` and returning appropriate HTTP 500 JSON payloads.





---

**`AzureapiController.php`**

* **Namespace / Base Class:** `App\Controllers\AzureapiController` extending `BaseController`.


* **Purpose:** Handles direct Microsoft Graph/Azure AD OAuth2 token exchanges, token persistence, and authenticated API resource requests.


* **Core Functionality:**
* `index()`: Reads locally cached access tokens and queries the Microsoft Graph `/v1.0/me` endpoint using Bearer authentication.


* `callback()`: Handles OAuth2 authorization code callbacks (`/azure-auth/callback?code=...`), exchanging codes for access tokens with `login.microsoftonline.com` using configured client credentials and scopes.


* `getAuthorizeUrl()`: Constructs state-secured Azure AD authorization URLs for front-end authentication flows.


* Token Management (`getLocalToken()`, `saveLocalToken()`): Reads and securely persists OAuth tokens to disk storage (`azure/tokens/access_token.txt`) with `0700` directory permissions.


* Includes isolated cURL execution logic (`curlRequest()`) and custom JSON response helpers (`jsonResponse()`).





---

**`AzureController.php`**

* **Namespace / Base Class:** `App\Controllers\AzureController` extending `BaseController`.


* **Purpose:** Provides a lightweight endpoint for executing Azure hello-world integration checks through the ORM layer.


* **Core Functionality:**
* `hello()`: Constructs a GET query targeting the `AzureHelloWorld` data source via `$this->orm->execute()`.


* Wraps execution in a try-catch block to return successful execution results or log exceptions and output HTTP 500 error responses.





---

**`AzureFoundryController.php`**

* **Namespace / Base Class:** `App\Controllers\AzureFoundryController` extending `BaseCloudController`.


* **Purpose:** Handles text generation/completion calls targeting Azure AI Foundry instances.


* **Core Functionality:**
* `getAiService()`: Lazy-loads an instance of `App\Services\Azure\AzureFoundryService` without overriding base constructor logic.


* `authenticate()`: Resolves Azure Foundry endpoints and API keys from global environment helpers or `$_ENV` arrays.


* `generateAction()`: Extracts and validates string prompts from input requests, verifies environment credentials, delegates completion tasks to `AzureFoundryService::sendCompletion()`, and outputs resulting JSON responses.
### File Technical Breakdown

**`BaseApiController.php`**

* **Namespace / Base Class:** `App\Controllers\BaseApiController` extending `BaseController`.


* **Purpose:** Serves as a base class for API-specific controller implementations within the framework.


* **Core Functionality:**
* Inherits core service access, JSON output handling, and logger instances from `BaseController`.





---

**`BaseCloudController.php`**

* **Namespace / Base Class:** `App\Controllers\BaseCloudController` extending `BaseController`.


* **Purpose:** Provides a base layout for cloud service provider controllers.


* **Core Functionality:**
* Defines the structural signature `baseCloudRequest()` for handling cloud API integrations.





---

**`BaseController.php`**

* **Namespace / Base Class:** Abstract `App\Controllers\BaseController`.


* **Purpose:** Central abstract base controller providing service retrieval, request parsing, response formatting, diagnostic execution, and Ollama integration.


* **Core Functionality:**
* **Service Instantiation:** Automatically instantiates core services during construction, including `Logger`, `Diagnostics`, `Location`, `Smarty`, `Session`, `PromptService`, `Orm`, and `Db`.


* **`runDiagnosticScript()`:** Safely executes whitelisted shell scripts (`rag_check.sh`, `worker_check.sh`, `ollama_check.sh`) using `escapeshellarg()` and returns parsed JSON output.


* **`getNeuralStatus()`:** Probes the Ollama service endpoint (`http://llm:11434/api/tags`) via cURL to evaluate model readiness against mandatory defaults (`llama3:latest`, `nomic-embed-text:latest`, `jina/jina-embeddings-v2-small-en:latest`).


* **`request()`:** Reads and parses incoming request data across `$_POST`, `$_GET`, and raw `php://input` streams, logging input payloads to `app.log`.


* **`curlRequest()`:** Executes parallel multi-cURL HTTP requests across an array of target endpoints with loopback SSL verification bypassing.


* **`render()` / `renderView()`:** Processes template views via the Smarty service.


* **`json()`:** Standardizes application output by setting headers, returning JSON payloads, and terminating execution.





---

**`BashController.php`**

* **Namespace / Base Class:** `App\Controllers\BashController` extending `BaseController`.


* **Purpose:** Provides terminal command execution capabilities over HTTP.


* **Core Functionality:**
* `terminal()`: Accepts a command string `$cmd`, executes it via `exec()`, and returns the output string in a JSON payload via `$this->json()`.





---

**`ChatController.php`**

* **Namespace / Base Class:** `App\Controllers\ChatController` extending `BaseController`.


* **Purpose:** Handles front-end user interactions and stages input questions for external service processing.


* **Core Functionality:**
* `ask()`: Receives JSON input containing user messages via `$this->getJsonInput()`.


* Generates unique chat identifiers (`uniqid('chat_')`) and persists request payloads to disk (`{chat_id}_req.json`) within the configured vector storage directory resolved via `Location::vectorStorage()`.


* Returns queued status and chat reference metadata in a standard JSON response.

### File Technical Breakdown

**`CvController.php`**

* **Namespace / Base Class:** `App\Controllers\CvController` extending `BaseController`.


* **Purpose:** Handles CV tailoring workflows by converting input documents and vacancy descriptions into structured payloads for processing and auditing.


* **Core Functionality:**
* `index()`: Resolves local template file paths, invokes `tailor()`, logs successfully processed operations using `App\Models\CvAuditModel`, and returns JSON responses.


* `tailor()`: Reads CV and vacancy files, base64 encodes binary CV data, structures JSON task payloads (`tailor_cv`), and dispatches requests via `$this->respond()`.





---

**`DhillonsController.php`**

* **Namespace / Base Class:** `App\Controllers\DhillonsController` extending `BaseController`.


* **Purpose:** Acts as a multi-system gateway controller for operational venue management and system integration (Square, OpenTable, Eventbrite, ClickUp, Google Calendar).


* **Core Functionality:**
* `createAgent()`: Parses natural-language instructions via `PromptService`, constructs agent condition maps, and registers pending agent records using `App\Models\AgentModel`.


* `query()`: Parses incoming natural language prompts, dispatches parallel ORM requests (`$this->orm->executeParallel()`) across configured integration targets, synthesizes responses via an Ollama model call, and saves execution logs to the `queries` table.


* `direct()`: Normalizes integration source aliases and routes direct REST requests to authorized upstream services via the ORM layer.





---

**`DocsController.php`**

* **Namespace / Base Class:** `App\Controllers\DocsController` extending `BaseController`.


* **Purpose:** Fetches query history and formats record URLs for document and PDF generation workflows.


* **Core Functionality:**
* `index()`: Queries the `queries` table in descending order, extracts parsed answer payloads from JSON content strings, generates endpoint view links (`php/docs/pdf/{id}`), and outputs structured JSON responses.





---

**`DocxController.php`**

* **Namespace / Base Class:** `App\Controllers\DocxController` extending `BaseController`.


* **Purpose:** Serves as an operational module template and handles framework database migrations.


* **Core Functionality:**
* `index()`: Serves module status information as JSON.


* `action()`: Validates incoming JSON stream payloads for write/update operations.


* `migrate()`: Instantiates `App\Services\Migrator`, runs database migration scripts, and outputs formatted execution summaries.





---

**`EmailsController.php`**

* **Namespace / Base Class:** `App\Controllers\EmailsController` extending `BaseController`.


* **Purpose:** Manages background email queueing, task file persistence, and database job tracking.


* **Core Functionality:**
* `queue()`: Accepts payload inputs and writes exclusive, atomic JSON task files to local disk storage (`storage/tasks/pending/email/{id}.json`) using the `Location` service.


* `test()`: Inserts record entries marked with status `waiting` into the `emails` database table and calls `job()`.


* `job()`: Persists email waiting jobs to disk (`storage/agents/emails/waiting/job_{id}.json`).

### File Technical Breakdown

**`EnterpriseController.php`**

* **Namespace / Base Class:** `App\Controllers\EnterpriseController` extending `BaseController`.


* **Purpose:** Serves as a central hub for operational data orchestration between Odoo ERP and AI analysis services.


* **Core Functionality:**
* Uses constructor dependency injection to load `OdooIntegrationService` and `AiAgentService`.


* `processOperationReport()`: Fetches operational metrics from Odoo via a document identifier and passes the resulting payload to the AI service for financial analysis.





---

**`EventAgentController.php`**

* **Namespace / Base Class:** `App\Controllers\EventAgentController` extending `BaseController`.


* **Purpose:** Synchronizes external event platform data, formats payloads semantically, and verifies AI pipeline availability.


* **Core Functionality:**
* `sync()`: Invokes `$this->orm->execute()` to fetch EventBrite data, processes the result using `PromptService::parse()`, and checks Ollama model status via `$this->getNeuralStatus()`.


* Returns structured JSON detailing prompt configuration and neural readiness, or responds with HTTP 503 if models are incomplete/offline.





---

**`FacebookController.php`**

* **Namespace / Base Class:** `App\Controllers\FacebookController` extending `BaseController`.


* **Purpose:** Integrates with the Facebook Graph API for user profile retrieval, page feed posting, and OAuth2 callback processing.


* **Core Functionality:**
* `get()`: Queries user identity details from the Graph API using `FacebookModel` and outputs the JSON response.


* `post()`: Fetches user page access tokens and publishes automated feed messages to target Facebook pages.


* `callback()`: Processes authorization code callbacks and exchanges them for access tokens via `FacebookModel::auth()`.





---

**`FileController.php`**

* **Namespace / Base Class:** `App\Controllers\FileController` extending `BaseController`.


* **Purpose:** Handles CSV upload ingestion, file sanitization, disk storage, and database job creation.


* **Core Functionality:**
* `upload()`: Accepts `$_FILES['csv_data']` uploads, strips control characters via regular expressions, and saves the sanitized file to the uploads directory using `$this->location->uploads()`.


* Inserts a new processing job record into the `jobs` database table with step tracking metadata and returns the generated job ID as JSON.





---

**`FormAutomationController.php`**

* **Namespace / Base Class:** `App\Controllers\FormAutomationController` extending `BaseController`.


* **Purpose:** Manages a two-stage form automation pipeline involving draft preparation and final submission triggers.


* **Core Functionality:**
* `submitDraft()`: Creates a pending database job and publishes a draft message event (`form.automation.task`) via messenger.


* `approveJob()`: Updates job status to `approved` and dispatches a final submission event payload via messenger.

### File Technical Breakdown

**`FormAutomationController.php`**

* **Namespace / Base Class:** `App\Controllers\FormAutomationController` extending `BaseController`.


* **Purpose:** Manages a two-stage form automation pipeline involving draft preparation and final submission triggers.


* **Core Functionality:**
* `submitDraft()`: Creates a pending database job (`pending_review`) and publishes a draft message event (`form.automation.task`) via messenger.


* `approveJob()`: Updates job status to `approved` and dispatches a final submission event payload via messenger.





---

**`GmailController.php`**

* **Namespace / Base Class:** `App\Controllers\GmailController` extending `BaseController`.


* **Purpose:** Returns profile data and digital footprint metadata matching standard Google/Gmail API user representations.


* **Core Functionality:**
* `index()`: Immediately calls and returns `$this->json($this->response())`.


* `response()`: Constructs a structured array mocking a `/users/me/profile` response payload containing user identity details, primary/work email addresses, phone numbers, home location data (Manchester, England), and audit timestamps.





---

**`GoogleapiController.php`**

* **Namespace / Base Class:** `App\Controllers\GoogleapiController` extending `BaseCloudController`.


* **Purpose:** Handles end-to-end Google OAuth2 token exchanges, local token storage, and user profile data retrieval.


* **Core Functionality:**
* `index()`: Reads locally cached access tokens and queries the Google userinfo endpoint (`oauth2/v3/userinfo`) using Bearer header authentication.


* `callback()`: Handles OAuth2 authorization code callbacks (`/google-auth/callback?code=...`), exchanges authorization codes for access tokens via `[oauth2.googleapis.com/token](https://oauth2.googleapis.com/token)`, logs debug output, and handles invalid grant errors.


* Token Persistence (`getLocalToken()`, `saveLocalToken()`): Manages localized access token persistence under `google/tokens/access_token.txt` with restricted directory permissions (`0700`).


* Features custom cURL execution logic (`curlRequest()`) and standard JSON response formatters (`jsonResponse()`).





---

**`GoogleController.php`**

* **Namespace / Base Class:** `App\Controllers\GoogleController` extending `BaseController`.


* **Purpose:** Evaluates connection status for the Google API service client wrapper.


* **Core Functionality:**
* `auth()`: Instantiates `App\Google\Client`, checks whether a valid access token is present on the client, and returns the status, connection state, and current timestamp as JSON.





---

**`HealthcareController.php`**

* **Namespace / Base Class:** `App\Controllers\HealthcareController` extending `BaseController`.


* **Purpose:** Coordinates clinical decision support, patient mobile data synchronization, electronic observations, and emergency department workflows.


* **Core Functionality:**
* Constructor dependency injection loads `ClinicalMLService`, `PatientMobileService`, `ElectronicObservationService`, and `EmergencyDepartmentService`.


* `executeCancerDetectionModel()`: Evaluates patient diagnostic data through clinical ML models.


* `processPatientCareUpdate()`: Synchronizes patient care updates from mobile devices.


* `calculatePatientRiskScore()`: Calculates real-time vital sign acuity and risk scores.


* `manageEmergencyDepartmentWorkflow()`: Tracks emergency department patient acuity and care paths.





---

**`HealthController.php`**

* **Namespace / Base Class:** `App\Controllers\HealthController` extending `BaseController`.


* **Purpose:** Serves as a centralized diagnostic check controller for probing infrastructure, databases, local Python services, and AI neural stacks.


* **Core Functionality:**
* `index()`: Supports a fast `shallow` mode for CI/CD checks. In standard mode, checks MariaDB database connectivity by fetching recent jobs, executes diagnostic shell scripts (`rag_check.sh`, `worker_check.sh`, `ollama_check.sh`), and fetches Ollama neural status.


* `checkRagService()`: Uses inherited `$this->respond()` to execute a GET ping against the Python RAG service health URL (`http://localhost:8765/health`).


* `checkGoogleService()`: Instantiates `GoogleController` to verify API wrapper availability.

### File Technical Breakdown

**`HomeController.php`**

* **Namespace / Base Class:** `App\Controllers\HomeController` extending `BaseController`.


* **Purpose:** Handles application home routes, core system status payloads, and administrative job logs.


* **Core Functionality:**
* Initializes session management via `src\Services\Session::getInstance()` in the constructor.


* `index()`: Outputs module operational status and server timestamp as JSON.


* `jobs()`: Queries records from the `jobs` table ordered by ID descending and returns them as JSON.


* `action()`: Receives JSON payloads via `php://input` to process generic write operations.





---

**`HotmailapiController.php`**

* **Namespace / Base Class:** `App\Controllers\HotmailapiController` extending `BaseController`.


* **Purpose:** Provides Microsoft Graph API OAuth2 integration for profile fetching and token management.


* **Core Functionality:**
* `index()`: Reads locally stored tokens and calls Microsoft Graph (`[https://graph.microsoft.com/v1.0/me](https://graph.microsoft.com/v1.0/me)`) via Bearer token authentication.


* `callback()`: Handles OAuth2 authorization codes (`/hotmail-auth/callback?code=...`), executing code exchanges against Microsoft identity servers using configured application constants (`HOTMAIL_CLIENT_ID`, `HOTMAIL_CLIENT_SECRET`, `HOTMAIL_REDIRECT_URI`).


* Local Token Storage (`getLocalToken()`, `saveLocalToken()`): Encapsulates persistence under `hotmail/tokens/access_token.txt` with strict directory permissions (`0700`).





---

**`IndeedApiController.php`**

* **Namespace / Base Class:** `App\Controllers\IndeedApiController` extending `BaseController`.


* **Purpose:** Scans snapshot directories and parses local HTML or RSS job listings from Indeed.


* **Core Functionality:**
* `index()`: Scans storage directory `snapshots` for `.html` files, ordering by newest first, and returns up to 20 parsed job listings.


* `parseSnapshotFile()`: Inspects file headers to route payloads to XML RSS parsing or HTML DOM processing.


* `parseHtmlSnapshot()`: Uses `DOMDocument` and `DOMXPath` to query elements (`job_seen_beacon`, `cardOutline`, `resultContent`) and extract roles, companies, summaries, and URLs.


* `parseRssSnapshot()`: Uses `SimpleXMLElement` to extract RSS title, link, and description nodes.





---

**`InfrastructureController.php`**

* **Namespace / Base Class:** `App\Controllers\InfrastructureController` extending `BaseController`.


* **Purpose:** Orchestrates system patching, compliance auditing, and virtual resource monitoring.


* **Core Functionality:**
* Uses constructor injection for `SystemOrchestrationService`, `ComplianceService`, and `VirtualizationService`.


* `deploySystemUpdates()`: Delegates system patching cycles for specified environments.


* `auditSystemSecurity()`: Audits security posture and regulatory compliance (e.g., GDPR, ISO27001, PCI DSS) for given node IDs.


* `monitorVirtualResource()`: Returns resource utilization metrics for host virtualization infrastructure.





---

**`IngestionController.php`**

* **Namespace / Base Class:** `App\Controllers\IngestionController` extending `BaseController`.


* **Purpose:** Handles target URL fetching, document cleaning, and snapshot persistence using synchronous and asynchronous pipelines.


* **Core Functionality:**
* `index()`: Fetches target web pages via `IngestionModel`, registers entries using `SnapshotsModel`, strips script/style/svg elements via `prepareFile()`, and saves raw and prepared snapshots.


* `queue()`: Generates executable shell scripts under `cmd/jobs/waiting/ingest_{ts}.sh` containing cURL fetch commands to dispatch asynchronous worker ingestion tasks.


* `snapshotsRaw()` & `snapshots()`: Writes raw HTML/XML files to `snapshots-raw` and cleaned HTML/XML files to `snapshots` storage paths.


