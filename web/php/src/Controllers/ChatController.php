<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Location;
use Exception;

/**
 * Handles the User Interface -> Python Neural Bridge
 */
class ChatController extends BaseController
{
    /**
     * POST /php/chat/ask
     */
    public function ask(): void
    {
        $data = $this->getJsonInput();
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            $this->json(['error' => 'Message cannot be empty'], 400);
            return;
        }

        try {
            /**
             * STEP 1: Hand-off to Python
             * We write the question to a 'pending' chat file.
             */
            $chatId = uniqid('chat_');
            $requestPath = Location::vectorStorage() . "/{$chatId}_req.json";
            
            file_put_contents($requestPath, json_encode([
                'id' => $chatId,
                'question' => $userMessage,
                'timestamp' => time()
            ]));

            /**
             * STEP 2: The Logic (Mental Model for today)
             * In our current 'Manual' stage, Python will pick this up.
             * For now, we will return a "Request Received" or call a mock 
             * response until we build the Python listener.
             */
            
            // Mocking the Python result for today's code-path draft:
            $this->json([
                'status' => 'queued',
                'chat_id' => $chatId,
                'message' => 'Question handed to Neural Engine.'
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}