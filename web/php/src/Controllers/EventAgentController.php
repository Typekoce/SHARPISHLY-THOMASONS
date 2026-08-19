<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

class EventAgentController extends BaseController
{
    /**
     * Fetch external event data, format via PromptService, and check AI pipeline readiness.
     */
    public function sync(): void
    {
        try {
            // 1. Leverage Orm.php to fetch EventBrite data via registry
            $eventbriteData = $this->orm->execute([
                'source' => 'EventBriteHello',
                'method' => 'GET'
            ]);

            // 2. Pass payload to PromptService for semantic structuring
            $formattedPrompt = $this->prompt->parse([
                'task'   => 'summarize_events',
                'events' => $eventbriteData
            ]);

            // 3. Check neural stack readiness before dispatching
            $neuralStatus = $this->getNeuralStatus();

            if (!$neuralStatus['synced']) {
                $this->json([
                    'status'  => 'degraded',
                    'message' => 'Neural pipeline incomplete or offline.',
                    'models'  => $neuralStatus['models']
                ], 503);
            }

            // 4. Return unified execution state
            $this->json([
                'status' => 'success',
                'data'   => [
                    'prompt' => $formattedPrompt,
                    'neural' => $neuralStatus
                ]
            ]);

        } catch (Throwable $e) {
            $this->logger->error("EventAgentController Error: " . $e->getMessage());
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}