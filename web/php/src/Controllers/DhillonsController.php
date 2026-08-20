<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AgentModel;
use Throwable;

class DhillonsController extends BaseController
{
    private const ALLOWED_SOURCES = [
        'Square',
        'OpenTable',
        'Eventbrite',
        'ClickUp',
        'GoogleCal',
    ];

    /**
     * Map lowercase key aliases to exact gateway casing.
     */
    private function normalizeSource(string $source): string
    {
        $map = [
            'square'     => 'Square',
            'opentable'  => 'OpenTable',
            'eventbrite' => 'Eventbrite',
            'clickup'    => 'ClickUp',
            'googlecal'  => 'GoogleCal',
            'google'     => 'GoogleCal',
        ];

        $key = strtolower(trim($source));
        return $map[$key] ?? ucfirst($key);
    }

    /**
     * Register an operational background agent.
     */
    public function createAgent(string $instruction = ''): array
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

            if (!$id) {
                return ['status' => 'error', 'message' => 'Failed to persist agent'];
            }

            return [
                'status'     => 'pending',
                'id'         => $id,
                'agent_name' => $agentName,
            ];
        } catch (Throwable $e) {
            $this->logger->error('Agent creation failed: ' . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
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

        $requestedSources = $this->request('sources');

        try {
            $conditions = $this->prompt->promptToConditions($promptText);

            $allTargets = [
                'square'     => 'Square',
                'opentable'  => 'OpenTable',
                'eventbrite' => 'Eventbrite',
                'clickup'    => 'ClickUp',
                'google'     => 'GoogleCal',
            ];

            $targets = $allTargets;
            if (is_array($requestedSources) && !empty($requestedSources)) {
                $validKeys = array_intersect($requestedSources, array_keys($allTargets));
                $targets   = array_intersect_key($allTargets, array_flip($validKeys));

                if (empty($targets)) {
                    $this->json([
                        'status'  => 'error',
                        'message' => 'No valid sources requested.',
                    ], 400);
                    return;
                }
            }

            $aggregatedData = $this->orm->executeParallel($targets);

            $synthesis = $this->orm->execute([
                'source' => 'Ollama',
                'action' => 'create',
                'data'   => [
                    'model'  => static::REQUIRED_MODELS[0],
                    'prompt' => "Dhillon's Query: {$promptText}\n\nContext:\n" . json_encode($aggregatedData),
                ],
            ]);

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

    /**
     * Direct endpoint helper for targeted sub-system checks.
     */
    public function direct(string $source = ''): void
    {
        $sourceName = $this->normalizeSource($source);

        if (!in_array($sourceName, self::ALLOWED_SOURCES, true)) {
            $this->json([
                'status'  => 'error',
                'message' => "Unsupported source: {$source}",
            ], 400);
            return;
        }

        try {
            $token  = $this->request('token');
            $params = $this->request('params');

            $conditions = [
                'source' => $sourceName,
                'method' => 'GET',
                'params' => is_array($params) ? $params : [],
            ];

            if ($token) {
                $conditions['token'] = $token;
            }

            $response = $this->orm->execute($conditions);

            $this->json([
                'status' => 'success',
                'source' => $sourceName,
                'data'   => $response,
            ]);
        } catch (Throwable $e) {
            $this->logger->error("Dhillons Direct Endpoint Error [{$sourceName}]: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}