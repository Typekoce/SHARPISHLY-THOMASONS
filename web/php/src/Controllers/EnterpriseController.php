<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Mining\OdooIntegrationService;
use App\Services\Mining\AiAgentService;

/**
 * EnterpriseController
 * Central hub for operational data orchestration between Odoo and AI services.
 */
class EnterpriseController extends BaseController {

    public function __construct(
        private OdooIntegrationService $odoo,
        private AiAgentService $ai
    ) {}

    /**
     * Processes operation reports by fetching data from the ERP and analyzing via AI.
     * 
     * @param string $documentId
     * @return array
     */
    public function processOperationReport(string $documentId): array {
        $data = $this->odoo->fetchOperationalData($documentId);
        
        if (empty($data)) {
            return ['status' => 'error', 'message' => 'No operational data found'];
        }

        return $this->ai->analyzeFinancials($data);
    }
}