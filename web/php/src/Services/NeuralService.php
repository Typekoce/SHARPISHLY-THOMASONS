<?php
declare(strict_types=1);

namespace App\Services;

class NeuralService extends BaseService {

    /**
     * Handover method: Sends the file to the ai-engine container.
     */
    public function ingest(string $filename, string $docId, array $meta = []): bool {
        $fullPath = $this->uploadPath . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fullPath)) {
            $this->log("Neural Ingest Error: File not found at $fullPath", 'ERROR');
            return false;
        }

        $payload = json_encode([
            'file_path'   => $fullPath,
            'document_id' => $docId,
            'metadata'    => $meta
        ]);

        $ch = curl_init("http://ai-engine:8000/ingest");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Using your 600s Nginx timeout settings
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->log("Neural Pipeline accepted document $docId", 'INFO');
            return true;
        }

        $this->log("Neural Pipeline rejected $docId (Status $statusCode): $response", 'ERROR');
        return false;
    }
}