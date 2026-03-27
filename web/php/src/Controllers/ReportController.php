<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use App\Core\Registry;
use App\Services\VectorSearchService;
use App\Services\OllamaService;
use App\Services\WordDocService;

class ReportController extends BaseController
{
    /**
     * POST /php/report/generate
     * Inherits $this->db, $this->loc, $this->logger, $this->smarty from BaseController.
     */
    public function generate(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = $input['query'] ?? '';

            if (empty($query)) {
                throw new Exception("Please provide a search query.");
            }

            // 1. Semantic Search (Internal Service)
            // We pull services directly from Registry or instantiate if they are 'Flat'
            $searchService = Registry::get(VectorSearchService::class);
            $searchResults = $searchService->search($query, 10);

            if (empty($searchResults)) {
                $this->json(['status' => 'empty', 'message' => 'No matching records.'], 404);
                return;
            }

            // 2. AI Entity Extraction (Ollama)
            $ollama = Registry::get(OllamaService::class);
            $contextString = implode("\n", array_column($searchResults, 'text'));
            
            $prompt = "Extract property data. Return ONLY JSON array [{\"ADDRESS\":\"\",\"MATERIAL\":\"\",\"YEAR\":\"\"}]. Context: " . $contextString;

            // Simplified: We assume OllamaService handles the curl/request internally
            $extractedData = json_decode($ollama->ask($prompt), true);

            if (!$extractedData) {
                throw new Exception("AI extraction failed.");
            }

            // 3. Word Document Generation
            $word = Registry::get(WordDocService::class);
            $reportData = $extractedData[0] ?? $extractedData;

            // Using inherited $this->loc to ensure paths stay consistent
            $reportFileName = $word->generateReport(
                'cladding_report_template.docx', 
                $reportData
            );

            $this->json([
                'status' => 'success',
                'download_url' => $this->loc->relative("/storage/reports/$reportFileName"),
                'preview' => $reportData
            ]);

        } catch (Exception $e) {
            $this->logger->error("Report Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}