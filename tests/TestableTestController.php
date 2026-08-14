<?php

declare(strict_types=1);

namespace App\Controllers;

class TestableTestController extends TestController
{
    public array $lastJsonPayload = [];
    public int $lastJsonCode = 200;

    protected function json(array $data, int $code = 200): void
    {
        $this->lastJsonPayload = $data;
        $this->lastJsonCode = $code;
    }

    public function getData(): array
    {
        return $this->lastJsonPayload;
    }
}