<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\DhillonsController;

class DhillonsControllerTest extends TestCase
{
    public function testDhillonsControllerInitialization(): void
    {
        $controller = new DhillonsController();
        
        $this->assertInstanceOf(\App\Services\PromptService::class, $controller->prompt);
        $this->assertInstanceOf(\App\Services\Orm::class, $controller->orm);
        $this->assertInstanceOf(\App\Services\Logger::class, $controller->logger);
    }
}