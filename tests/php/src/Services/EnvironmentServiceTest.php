<?php
# Location: tests/php/src/Services/EnvironmentServiceTest.php

class EnvironmentServiceTest {

    public function __construct() {
        $this->audit('DB_HOST');
        $this->audit('REDIS_HOST');
        $this->audit('OLLAMA_HOST');
    }

    public function getHost($key) {
        return getenv($key);
    }

    private function audit($key) {
        $value = $this->getHost($key);
        if ($value) {
            echo "✅ $key: Found ($value)\n";
        } else {
            echo "❌ $key: NOT FOUND. Check docker-compose env_file.\n";
        }
    }
}

// Execute immediately
new EnvironmentServiceTest();