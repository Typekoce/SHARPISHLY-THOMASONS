<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\EmbeddingService;
use Exception;

class SearchController extends BaseController
{
    private EmbeddingService $embedder;

    public function __construct()
    {
        parent::__construct();
        $this->embedder = new EmbeddingService();
    }

    /**
     * GET /php/search
     * Params: q (the search query)
     */
    public function query(): void
    {
        $queryString = $_GET['q'] ?? '';

        if (empty($queryString)) {
            $this->json(['error' => 'Query string is required'], 400);
            return;
        }

        try {
            // 1. Convert user's question into a Vector (Neural Search)
            // We use the same service that handled the ingestion
            $queryVector = $this->embedder->getVectorOnly($queryString);
            $vectorCsv = implode(',', $queryVector);

            // 2. Call the Java Bridge in "search" mode (findTopK)
            $binPath = '/var/www/html/llm/foozie-vector-db/bin';
            $javaCmd = sprintf(
                'java -cp %s App search %s 3 2>&1', // '3' is the Top-K results
                escapeshellarg($binPath),
                escapeshellarg($vectorCsv)
            );

            $output = shell_exec($javaCmd);

            // 3. Parse and return the results
            // Your Java App.java should output JSON or structured text
            $this->json([
                'query' => $queryString,
                'matches' => $this->parseJavaOutput($output),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function parseJavaOutput(?string $output): array
    {
        if (!$output) return [];
        // Assuming your Java App outputs matches in a specific format
        // This is where you'd explode lines or json_decode the Java result
        return array_filter(explode("\n", trim($output)));
    }
}