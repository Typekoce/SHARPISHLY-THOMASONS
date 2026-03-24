<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Registry;
use App\Services\Location;
use App\Services\VectorSearchService;
use App\Services\OllamaService;
use App\Services\WordDocService;
use Exception;

class ReportController extends BaseController
{
    private Location $location;

    public function __construct()
    {
        parent::__construct();
        $this->location = Registry::make(Location::class);
    }

    /**
     * POST /php/report/generate
     * Orchestrates: Semantic Search -> AI Extraction -> Word Doc Generation
     */
    public function generate(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = $input['query'] ?? '';

            if (empty($query)) {
                throw new Exception("Please provide a search query (e.g., 'Pre-1980s cladding').");
            }

            // 1. Semantic Search (Java Bridge)
            // Pulling the search service via Registry
            $searchService = Registry::make(VectorSearchService::class);
            $searchResults = $searchService->search($query, 10);

            if (empty($searchResults)) {
                $this->json(['status' => 'empty', 'message' => 'No matching property records found.'], 404);
                return;
            }

            // 2. AI Entity Extraction (Ollama)
            // We turn the raw vector chunks into a structured data array
            $ollama = Registry::make(OllamaService::class);
            $contextString = implode("\n", array_column($searchResults, 'text'));
            
            $prompt = "You are a professional building surveyor. Extract property data from the context.
                       Return ONLY a JSON array of objects with keys: ADDRESS, MATERIAL, YEAR.
                       Context: " . $contextString;

            $rawJson = $ollama->ask($prompt);
            $extractedData = json_decode($rawJson, true);

            if (!$extractedData) {
                throw new Exception("AI failed to extract structured property data.");
            }

            // 3. Word Document Generation
            $word = Registry::make(WordDocService::class);
            
            // We'll use the first match for a single-property report, 
            // or pass the whole array if your template supports loops.
            $reportData = $extractedData[0] ?? $extractedData;

            $reportFileName = $word->generateReport(
                'cladding_report_template.docx', 
                $reportData
            );

            // 4. Return the relative download path
            $this->json([
                'status' => 'success',
                'download_url' => '/storage/reports/' . $reportFileName,
                'preview' => $reportData
            ]);

        } catch (Exception $e) {
            error_log("Report Generation Error: " . $e->getMessage());
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}