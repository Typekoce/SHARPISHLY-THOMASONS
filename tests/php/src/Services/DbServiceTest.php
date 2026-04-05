<?php
# Location: tests/php/src/Services/EnvironmentServiceTest.php

declare(strict_types=1);

require_once __DIR__ . '/BaseServiceTest.php';

class EnvironmentServiceTest extends BaseServiceTest {
    private \App\Services\EnvironmentService $env;

    public function __construct() {
        parent::__construct();
        $this->requireService('EnvironmentService');
        $this->env = \App\Services\EnvironmentService::getInstance();
        $this->runAudit();
    }

    protected function runAudit(): void {
        $keys = ['DB_HOST', 'REDIS_HOST', 'OLLAMA_HOST', 'APP_ENV'];
        $failed = false;

        foreach ($keys as $key) {
            $val = $this->env->get($key);
            if ($val) {
                echo "✅ $key: Found ($val)\n";
            } else {
                echo "❌ $key: NOT FOUND\n";
                $failed = true;
            }
        }
        exit($failed ? 1 : 0);
    }
}

new EnvironmentServiceTest();