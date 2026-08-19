<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\TestableTestController;

class TestControllerTest extends TestCase
{
    public function testControllerDataStructure(): void
    {
        $controller = new TestableTestController();
        $controller->index();

        $data = $controller->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('class', $data);
        $this->assertArrayHasKey('function', $data);
        $this->assertArrayHasKey('google_api', $data);
        $this->assertArrayHasKey('recent_work', $data);

        $this->assertArrayHasKey('llm', $data); // Added missing assertion

        $this->assertEquals('TestController', $data['class']);
        $this->assertIsArray($data['recent_work']['controllers']);
        $this->assertIsArray($data['recent_work']['documentation']);

        $this->assertContains('CONTEXT.md', $data['recent_work']['documentation']);
        $this->assertContains('AzureFoundryController.php', $data['recent_work']['controllers']);
    }
}