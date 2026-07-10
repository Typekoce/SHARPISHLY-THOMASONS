<?php

namespace App\Controllers;

class SmsController extends BaseController
{
    /**
     * Usage: http://localhost/php/sms/rag?query=your_message_here
     */
    public function rag()
    {
        // 1. Retrieve query from GET parameter or POST request body
        $msg = $_GET['query'] ?? $this->request('query');

        if (empty($msg)) {
            return $this->json(['error' => 'No message provided'], 400);
        }

        try {
            // 2. Prepare payload and validate encoding
            $payload = json_encode(['query' => $msg]);
            if ($payload === false) {
                throw new \RuntimeException('Unable to encode request payload');
            }

            // 3. Transport via BaseController shared respond() method
            $response = $this->respond($payload, null, 'POST');
            if (!$response) {
                throw new \RuntimeException('RAG Service unreachable');
            }

            // 4. Validate JSON integrity
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON returned by RAG service');
            }

            // 5. Standardized output
            return $this->json([
                'msg' => $msg,
                'response' => $data,
            ]);

        } catch (\Throwable $e) {
            // 6. Centralized logging as per OPERATIONS.md
            $this->logger->error('SmsController Error: ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}