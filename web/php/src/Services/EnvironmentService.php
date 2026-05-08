<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ENVIRONMENT SERVICE (SSOT)
 */
class EnvironmentService extends BaseService {
    private static ?EnvironmentService $instance = null;
    private array $config = [];
    
    // REMOVED: private Location $location; <-- This was the Fatal Error trigger.

    /**
     * Protected constructor for Singleton pattern.
     */
    protected function __construct() {
        // 1. Call parent to initialize public $this->location and paths
        parent::__construct(); 
        
        // 2. Load the infrastructure config
        $this->ingest();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            // Since the constructor is protected/private, we instantiate it here.
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * CONTEXT DETECTION
     */
    public function isDevMode(): bool {
        $appDev = $this->get('APP_DEV');
        $appEnv = $this->get('APP_ENV');

        return ($appDev === 'true' || $appDev === '1' || $appEnv === 'development' || $appEnv === 'local');
    }

    public function isProductionMode(): bool {
        return $this->get('APP_ENV') === 'production';
    }

    /**
     * CORE GETTER
     */
    public function get(string $key, $default = null) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        $value = getenv($key);
        return ($value === false) ? $default : $value;
    }

    /**
     * INFRASTRUCTURE INGESTION
     */
    private function ingest(): void {
        // Now using $this->location inherited from BaseService
        $this->parseRegex($this->location->makefile(), [
            'SYS_VERSION' => '/VERSION\s*[:=]\s*(.+)/',
            'SYS_NAME'    => '/PROJECT_NAME\s*[:=]\s*(.+)/'
        ]);

        $this->parseRegex($this->location->dockerCompose(), [
            'DB_START_PERIOD' => '/db:.*?start_period:\s*(\d+s)/s'
        ]);

        $this->parseRegex($this->location->nginxConfig(), [
            'NGINX_MAX_UPLOAD' => '/client_max_body_size\s+([\w\d]+);/'
        ]);

        if (file_exists($this->location->aiRequirements())) {
            $this->config['AI_LIBS'] = file($this->location->aiRequirements(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
    }

    private function parseRegex(string $path, array $patterns): void {
        if (!file_exists($path)) return;
        $content = file_get_contents($path);
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $this->config[$key] = trim($matches[1]);
            }
        }
    }
}