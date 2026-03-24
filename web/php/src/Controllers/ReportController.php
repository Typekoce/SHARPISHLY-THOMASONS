<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\OllamaService; // We'll refactor callOllama here
use App\Services\VectorSearchService;
use App\Services\WordDocService;

class ReportController extends BaseController
{
    /**
     * POST /php/report/generate
     * Payload: { "query": "residential properties with pre-1980s cladding" }
     */
    public function generate(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $query = $input['query'] ?? '';

        // 1. Find the "Facts" in the Vector DB
        $searchService = new VectorSearchService();
        $context = $searchService->search($query, 10); // Get top 10 matches

        // 2. Ask Ollama to format the data for a table (Entity Extraction)
        $ollama = new OllamaService();
        $extractionPrompt = "Extract a list of properties from this context. 
                             Return ONLY valid JSON with keys: address, cladding_type, year.
                             Context: " . $context;
        
        $jsonData = $ollama->ask($extractionPrompt);

        // 3. Inject into Word Template
        $word = new WordDocService();
        $filePath = $word->createFromTemplate(
            'cladding_report_template.docx', 
            json_decode($jsonData, true)
        );

        $this->json(['download_url' => '/storage/reports/' . basename($filePath)]);
    }
}