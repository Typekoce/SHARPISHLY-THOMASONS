<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Support\TicketOrchestrationService;
use App\Services\Support\ComplianceService;

class SupportController extends BaseController {

    public function __construct(
        private TicketOrchestrationService $ticketService,
        private ComplianceService $compliance
    ) {}

    // Maps to: "Build internal tooling and automations using APIs"
    public function triageTicket(string $ticketId): array {
        return $this->ticketService->autoTriage($ticketId);
    }

    // Maps to: "Run customer onboarding and KYC flows"
    public function verifyCustomer(int $userId): array {
        return $this->compliance->runKycFlow($userId);
    }
}