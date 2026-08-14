<?php

declare(strict_types=1);

namespace App\Controllers;

class DhillonsController extends BaseController
{
    /**
     * Target venue system endpoints covering Square POS, OpenTable, Eventbrite,
     * ClickUp, and Google Workspace integrations.
     */
    private array $systemEndpoints = [
        'square'     => 'https://connect.squareup.com/v2/reports/sales',
        'opentable'  => 'https://api.opentable.com/v2/bookings',
        'eventbrite' => 'https://www.eventbriteapi.com/v3/organizations/me/events/',
        'clickup'    => 'https://api.clickup.com/api/v2/team',
        'google'     => 'https://www.googleapis.com/calendar/v3/calendars/primary/events',
    ];

    /**
     * Multi-System Gateway Endpoint: /php/dhillons/query (POST)
     */
    public function query(): void
    {
        // 1. Unified input extraction via BaseController helper
        $promptText = (string) (
            $this->request('prompt') ??
            'What were our sales last week across each venue, how does that compare to the previous week, and do we have any upcoming bookings that could affect this week\'s forecast?'
        );

        try {
            // 2. Local NLP parsing & RAG filter condition extraction
            // $promptService = new PromptService();
            $conditions    = $this->prompt->promptToConditions($promptText);

            // 3. Concurrent non-blocking HTTP dispatch across system APIs
            $aggregatedData = $this->fetchSystemData($this->systemEndpoints);

            // 4. Synthesize context via Orm router using BaseController neural model defaults
            // $orm = new Orm();
            $synthesis = $this->orm->execute([
                'source' => 'Ollama',
                'action' => 'create',
                'data'   => [
                    'model'  => static::REQUIRED_MODELS[0], // 'llama3.1:latest'
                    'prompt' =>
                        "Dhillon's Brewery Operational Intelligence Query: {$promptText}\n\n" .
                        "RAG Filters: " . json_encode($conditions, JSON_UNESCAPED_SLASHES) . "\n\n" .
                        "Aggregated Live Context:\n" . json_encode($aggregatedData, JSON_UNESCAPED_SLASHES),
                ],
            ]);

            // 5. Persist query history using BaseController DB & timestamp utilities
            $this->db->save('queries', [
                'query'      => $promptText,
                'response'   => json_encode($synthesis, JSON_UNESCAPED_SLASHES),
                'created_at' => $this->timestamp(),
            ]);

            // 6. Standardized JSON output via BaseController
            $this->json([
                'status'     => 'completed',
                'company'    => 'Dhillon\'s Brewery',
                'prompt'     => $promptText,
                'conditions' => $conditions,
                'synthesis'  => $synthesis,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Dhillon\'s Gateway query failed: ' . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Native parallel HTTP dispatch across system endpoints.
     */
    private function fetchSystemData(array $endpoints): array
    {
        return $this->curlRequest($endpoints);
    }
}