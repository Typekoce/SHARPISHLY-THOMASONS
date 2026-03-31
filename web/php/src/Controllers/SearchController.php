<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\NeuralService;
use App\Services\VectorDb; // New Service
use Throwable;

class SearchController extends BaseController
{
    /**
     * POST /php/search
     * Body: { "query": "What are the prices for Nike?" }
     */
    public function query(): void
    {
        try {
            // 1. Get the JSON input from the SPA
            $input = json_decode(file_get_contents('php://input'), true);
            $queryText = $input['query'] ?? '';

            if (empty($queryText)) {
                $this->json(['status' => 'error', 'message' => 'Query is empty'], 400);
                return;
            }

            // 2. Turn the Question into a Vector
            $neural = new NeuralService();
            $queryVector = $neural->getEmbedding($queryText);

            if (!$queryVector) {
                throw new \Exception("Neural Engine failed to vectorize the query.");
            }

            // 3. Search via specialized VectorDb service
            $vectorDb = new VectorDb();
            $matches = $vectorDb->search($queryVector, 5);

            // 4. Return the "Context" to the SPA
            $this->json([
                'status'  => 'success',
                'query'   => $queryText,
                'results' => $matches, 
                'count'   => count($matches)
            ]);

        } catch (Throwable $e) {
            $this->logger->log("Search Error: " . $e->getMessage(), 'ERROR');
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
