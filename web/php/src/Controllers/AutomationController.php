<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\NHS\RpaOrchestrationService;
use App\Services\NHS\PowerPlatformService;
use App\Services\NHS\GovernanceService;

class AutomationController extends BaseController {

    public function __construct(
        private RpaOrchestrationService $rpaService,
        private PowerPlatformService $powerService,
        private GovernanceService $govService
    ) {}

    // Maps to: Develop, test, and deploy RPA solutions
    public function executeRoboticTask(string $processId, array $data): array {
        $this->govService->validateCompliance($processId);
        return $this->rpaService->runAutomation($processId, $data);
    }

    // Maps to: Assist in Microsoft Power Platform implementation
    public function deployPowerAppIntegration(array $config): void {
        $this->powerService->configureIntegration($config);
    }

    // Maps to: Process improvement and documentation
    public function generateProcessReport(string $processId): array {
        return $this->govService->generateAuditTrail($processId);
    }
}