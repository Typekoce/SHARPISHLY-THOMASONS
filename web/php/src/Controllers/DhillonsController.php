<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentModel;
use Throwable;

class DhillonsController extends BaseController
{
    /**
     * Register an operational background agent.
     */
    public function createAgent(string $instruction = ''): ?array
    {
        $instruction = $instruction ?: (string) ($this->request('instruction') ?? 'Automate venue operational sync');

        try {
            $content    = $this->prompt->read($instruction);
            $conditions = $this->prompt->promptToConditions($instruction);
            $agentName  = 'dhillons-agent-' . substr(md5($instruction . microtime()), 0, 6);

            $id = (new AgentModel())->create([
                'agent_name'  => $agentName,
                'description' => $instruction,
                'content'     => json_encode($content, JSON_UNESCAPED_SLASHES),
                'pref'        => json_encode($conditions, JSON_UNESCAPED_SLASHES),
                'status'      => 'pending',
                'created_at'  => $this->timestamp(),
            ]);

            return $id ? ['id' => $id, 'agent_name' => $agentName, 'status' => 'pending'] : null;
        } catch (Throwable $e) {
            $this->logger->error('Agent creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Multi-System Gateway Endpoint: /php/dhillons/query (POST)
     */
    public function query(): void
    {
        $promptText = (string) (
            $this->request('prompt') ??
            'Automate multi-venue operational sync: Compare Square POS sales, check OpenTable bookings, and sync ClickUp tasks.'
        );

        try {
            $conditions = $this->prompt->promptToConditions($promptText);

            // Parallel dispatch via Orm endpoint keys
            $aggregatedData = $this->orm->executeParallel([
                'square'     => 'Square',
                'opentable'  => 'OpenTable',
                'eventbrite' => 'Eventbrite',
                'clickup'    => 'ClickUp',
                'google'     => 'GoogleCal',
            ]);

            // Synthesize via Ollama
            $synthesis = $this->orm->execute([
                'source' => 'Ollama',
                'action' => 'create',
                'data'   => [
                    'model'  => static::REQUIRED_MODELS[0],
                    'prompt' => "Dhillon's Query: {$promptText}\n\nContext:\n" . json_encode($aggregatedData),
                ],
            ]);

            // Save history
            $this->db->save('queries', [
                'query'      => $promptText,
                'response'   => json_encode($synthesis, JSON_UNESCAPED_SLASHES),
                'created_at' => $this->timestamp(),
            ]);

            $this->json([
                'status'     => 'completed',
                'company'    => "Dhillon's Brewery",
                'prompt'     => $promptText,
                'conditions' => $conditions,
                'agent'      => $this->createAgent($promptText),
                'synthesis'  => $synthesis,
            ]);

        } catch (Throwable $e) {
            $this->logger->error("Dhillon's Gateway query failed: " . $e->getMessage());
            $this->json(['status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }
}