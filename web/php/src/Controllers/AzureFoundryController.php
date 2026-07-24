<?php

namespace App\Controllers;

use App\Services\Azure\AzureFoundryService;

class AzureFoundryController extends BaseCloudController {

    private AzureFoundryService $aiService;

    /**
     * Lazy-load or instantiate service without overriding BaseController constructor logic
     */
    private function getAiService(): AzureFoundryService {
        if (!isset($this->aiService)) {
            $this->aiService = new AzureFoundryService();
        }
        return $this->aiService;
    }

    /**
     * Load credentials relying on global env/bootstrap helpers
     */
    protected function authenticate(): array {
        $env = function_exists('get_env') ? get_env() : [];

        return [
            'endpoint' => $env['AZURE_FOUNDRY_ENDPOINT'] ?? $_ENV['AZURE_FOUNDRY_ENDPOINT'] ?? '',
            'api_key'  => $env['AZURE_FOUNDRY_API_KEY'] ?? $_ENV['AZURE_FOUNDRY_API_KEY'] ?? '',
        ];
    }

    public function getEndpoint(): string {
        $auth = $this->authenticate();
        return $auth['endpoint'];
    }

    /**
     * Handles /api/ai/generate requests
     */
    public function generateAction(): void {
        // Retrieve decoded JSON data via BaseController request helper
        $prompt = $this->request('prompt');

        if (!is_string($prompt) || trim($prompt) === '') {
            $this->json([
                'success' => false,
                'error'   => 'Invalid or missing "prompt" field in payload.'
            ], 400);
        }

        // Validate credentials configuration
        $auth = $this->authenticate();
        if (empty($auth['endpoint']) || empty($auth['api_key'])) {
            $this->json([
                'success' => false,
                'error'   => 'Azure AI Foundry credentials are missing or unconfigured.'
            ], 500);
        }

        // Delegate to service layer
        $result = $this->getAiService()->sendCompletion($auth, trim($prompt));

        // Output JSON utilizing BaseController json helper
        $statusCode = ($result['success'] ?? false) ? 200 : 500;
        $this->json($result, $statusCode);
    }
}