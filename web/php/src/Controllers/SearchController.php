<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\NeuralService;
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

            // 2. Turn the Question into a Vector (The "Query Vector")
            $neural = new NeuralService();
            $queryVector = $neural->getEmbedding($queryText);

            if (!$queryVector) {
                throw new \Exception("Neural Engine failed to vectorize the query.");
            }

            // 3. Search MariaDB for the closest matches
            // We pass the vector to a specialized Db method
            $matches = $this->db->vectorSearch('vectors', $queryVector, 5);

            // 4. Return the "Context" to the SPA
            $this->json([
                'status'  => 'success',
                'query'   => $queryText,
                'results' => $matches, // These are the rows from your CSVs
                'count'   => count($matches)
            ]);

        } catch (Throwable $e) {
            $this->logger->log("Search Error: " . $e->getMessage(), 'ERROR');
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
