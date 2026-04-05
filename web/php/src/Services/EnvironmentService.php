<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ENVIRONMENT SERVICE (SSOT)
 * Responsible for detecting execution context and ingesting infrastructure config.
 * Extends BaseService as per Thomasons V3 architecture.
 */
class EnvironmentService extends BaseService {
    private static ?EnvironmentService $instance = null;
    private array $config = [];
    private Location $location;

    protected function __construct() {
        $this->location = new Location();
        $this->ingest();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * CONTEXT DETECTION
     * Determines if the application is in Development mode.
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
     * Checks internal config array (ingested) then falls back to getenv().
     */
    public function get(string $key, $default = null) {
        // Check ingested infra config first
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        // Fallback to system environment variables
        $value = getenv($key);
        return ($value === false) ? $default : $value;
    }

    /**
     * INFRASTRUCTURE INGESTION
     * populates $this->config using the Location SSOT.
     */
    private function ingest(): void {
        // 1. Ingest Makefile (System Metadata)
        $this->parseRegex($this->location->makefile(), [
            'SYS_VERSION' => '/VERSION\s*[:=]\s*(.+)/',
            'SYS_NAME'    => '/PROJECT_NAME\s*[:=]\s*(.+)/'
        ]);

        // 2. Ingest Docker Compose (Networking/Health)
        $this->parseRegex($this->location->dockerCompose(), [
            'DB_START_PERIOD' => '/db:.*?start_period:\s*(\d+s)/s'
        ]);

        // 3. Ingest Nginx (Limits)
        $this->parseRegex($this->location->nginxConfig(), [
            'NGINX_MAX_UPLOAD' => '/client_max_body_size\s+([\w\d]+);/'
        ]);

        // 4. Ingest AI Requirements
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