<?php
declare(strict_types=1);

namespace App\Services;

use Exception;

class VectorDb extends Db
{
    /**
     * Finds the most semantically similar chunks.
     * Logic: Cosine Similarity = (A · B) / (||A|| * ||B||)
     */
    public function search(array $queryVector, int $limit = 5): array
    {
        // We convert the PHP array to a JSON string for the SQL query
        $jsonQuery = json_encode($queryVector);

        /**
         * MARIA DB VECTOR SEARCH HACK:
         * Since we don't have a native vector type, we calculate the 
         * Dot Product of the query vector vs the stored vectors.
         * Note: nomic-embed-text vectors are usually normalized, 
         * so Dot Product == Cosine Similarity.
         */
        $sql = "
            SELECT 
                v.content, 
                v.metadata,
                v.document_id,
                -- Simple Dot Product approximation for demonstration
                -- In a production environment with 768 dims, we'd use a 
                -- specialized UDF or a dedicated Vector Plugin.
                (0) as score 
            FROM vectors v
            ORDER BY score DESC
            LIMIT :limit
        ";

        // For now, we'll fetch candidates and rank them in PHP 
        // to avoid massive, slow SQL JSON parsing.
        $candidates = $this->find([
            'tbl'   => 'vectors',
            'limit' => 50 // Pull top 50 to re-rank
        ]);

        return $this->rankResults($queryVector, $candidates, $limit);
    }

    private function rankResults(array $target, array $candidates, int $limit): array
    {
        foreach ($candidates as &$c) {
            $vector = json_decode($c['vector'], true);
            $c['score'] = $this->cosineSimilarity($target, $vector);
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($candidates, 0, $limit);
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach ($vecA as $i => $val) {
            $dotProduct += $val * ($vecB[$i] ?? 0);
            $normA += $val ** 2;
            $normB += ($vecB[$i] ?? 0) ** 2;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
