<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\BaseController;

/**
 * Concrete implementation of BaseController for unit testing.
 * Intercepts json() calls to avoid terminating PHPUnit execution.
 */
class ConcreteBaseController extends BaseController
{
    public array $lastJsonOutput = [];
    public int $lastJsonCode = 200;

    protected function json(array $data, int $code = 200): void
    {
        $this->lastJsonOutput = $data;
        $this->lastJsonCode = $code;
    }

    public function callRunDiagnosticScript(string $scriptName): array
    {
        return $this->runDiagnosticScript($scriptName);
    }
}

class BaseControllerTest extends TestCase
{
    private ConcreteBaseController $controller;

    protected function setUp(): void
    {
        $this->controller = new ConcreteBaseController();
    }

    public function testServiceInitialization(): void
    {
        $this->assertInstanceOf(\App\Services\PromptService::class, $this->controller->prompt);
        $this->assertInstanceOf(\App\Services\Orm::class, $this->controller->orm);
        $this->assertInstanceOf(\App\Services\Logger::class, $this->controller->logger);
    }

    public function testTimestampFormatting(): void
    {
        $timestamp = $this->controller->timestamp();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp);
    }

    public function testDiagnosticScriptWhitelistGuard(): void
    {
        $result = $this->controller->callRunDiagnosticScript('unauthorized_script.sh');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Unauthorized script execution attempt.', $result['message']);
    }

    public function testRequestReturnsArrayByDefault(): void
    {
        $requestData = $this->controller->request();
        $this->assertIsArray($requestData);
    }
}