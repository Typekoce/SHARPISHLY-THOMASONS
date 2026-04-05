<?php
# Location: tests/php/src/Services/BaseServiceTest.php

declare(strict_types=1);

require_once __DIR__ . '/../../../../src/Services/Location.php';

abstract class BaseServiceTest {
    protected \App\Services\Location $location;
    protected string $root;

    public function __construct() {
        $this->location = new \App\Services\Location();
        $this->root = $this->location->projectRoot;
        $this->bootstrap();
    }

    private function bootstrap(): void {
        require_once $this->root . '/src/Services/BaseService.php';
    }

    protected function requireService(string $name): void {
        require_once $this->root . "/src/Services/{$name}.php";
    }

    abstract protected function runAudit(): void;
}