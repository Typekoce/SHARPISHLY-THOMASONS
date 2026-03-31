<?php
declare(strict_types=1);

namespace App\Services;

/**
 * NEURAL SERVICE
 * Bridges the PHP application with the Python AI Engine.
 */
class NeuralService extends BaseService 
{
    /**
     * Handover method: Dispatches document metadata to the Neural Pipeline.
     * * @param string $filename The name of the file in the shared upload directory.
     * @param string $docId    The UUID of the document record.
     * @param array  $meta     Optional metadata (author, tags, etc.)
     * @return bool            True if the AI Engine accepted the job.
     */
    public function ingest(string $filename, string $docId, array $meta = []): bool 
    {
        // 1. Resolve the path using the parent's uploadPath
        $fullPath = $this->uploadPath . DIRECTORY_SEPARATOR . $filename;

        // 2. Defensive check: Ensure the file actually exists in the shared volume
        if (!file_exists($fullPath)) {
            $this->log("Neural Ingest Error: File not found at $fullPath", 'ERROR', [
                'doc_id' => $docId
            ]);
            return false;
        }

        // 3. Trigger the Handshake via BaseService helper
        $response = $this->postJson("{$this->aiEndpoint}/ingest", [
            'file_path'   => $fullPath,
            'document_id' => $docId,
            'metadata'    => $meta
        ]);

        // 4. Evaluate response (Python FastAPI returns 202 for background tasks usually)
        if ($response['code'] >= 200 && $response['code'] < 300) {
            $this->log("Neural Pipeline accepted document", 'INFO', [
                'doc_id' => $docId,
                'status' => $response['code']
            ]);
            return true;
        }

        // 5. Log failure for debugging
        $this->log("Neural Pipeline rejected handover", 'ERROR', [
            'doc_id' => $docId,
            'status' => $response['code'],
            'body'   => $response['body']
        ]);

        return false;
    }
}