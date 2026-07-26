<?php

use PHPUnit\Framework\TestCase;

class TestControllerTest extends TestCase
{
    public function testControllerDataStructure(): void
    {
        // Mock or instantiate your controller instance
        $controller = new TestController();
        
        // Execute the method (assuming it returns or formats the $data array)
        $data = $controller->index();

        // 1. Assert required top-level keys exist
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('class', $data);
        $this->assertArrayHasKey('function', $data);
        $this->assertArrayHasKey('google_api', $data);
        $this->assertArrayHasKey('recent_work', $data);

        // 2. Assert structural details
        $this->assertEquals('TestController', $data['class']);
        $this->assertIsArray($data['recent_work']['controllers']);
        $this->assertIsArray($data['recent_work']['documentation']);

        // 3. Assert specific payload contents
        $this->assertContains('CONTEXT.md', $data['recent_work']['documentation']);
        $this->assertContains('AzureFoundryController.php', $data['recent_work']['controllers']);
    }
}