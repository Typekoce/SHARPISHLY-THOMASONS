<?php

declare(strict_types=1);

namespace App\Services;

class PromptService extends BaseService
{
    /**
     * Regex semantic extraction rules with named capture groups.
     * Keys correspond directly to destination database tables.
     */
    private array $patterns = [
        'emails' => [
            'pattern' => '/\b(?<action>email|send|message)\b(?:\s+to\s+(?<email>\S+@\S+|\w+))?(?:\s+(?:about|saying)\s+["\']?(?<content>.*?)["\']?)?$/i',
            'defaults' => ['status' => 'pending'],
        ],
        'agents' => [
            'pattern' => '/\b(?<action>create|build|spawn)\b\s+(?:an?\s+)?agent\b(?:\s+(?:named|called)\s+["\']?(?<agent_name>[\w\s]+?)["\']?)?(?:\s+(?:to|for)\s+(?<description>.*))?$/i',
            'defaults' => ['category' => 'automation', 'status' => 'pending'],
        ],
        'terminal' => [
            'pattern' => '/\b(?<action>run|execute|exec)\b\s+command\s+["\']?(?<command>.*?)["\']?$/i',
            'defaults' => ['status' => 'queued'],
        ],
        'queries' => [
            'pattern' => '/\b(?<action>find|search|fetch|query)\b\s+["\']?(?<content>.*?)["\']?$/i',
            'defaults' => ['status' => 'new'],
        ],
    ];

    /**
     * Parses dynamic user text into structured semantic components via regex.
     *
     * @return array{table: string, action: string, payload: array}
     */
    public function read(string $text = ''): array
    {
        $input = trim($text);

        foreach ($this->patterns as $table => $config) {
            if (preg_match($config['pattern'], $input, $matches)) {
                $extracted = array_filter(
                    $matches,
                    fn($k) => is_string($k) && isset($matches[$k]) && $matches[$k] !== '',
                    ARRAY_FILTER_USE_KEY
                );

                return [
                    'table'   => $table,
                    'action'  => strtolower($extracted['action'] ?? 'process'),
                    'payload' => array_merge(
                        $config['defaults'],
                        $extracted,
                        [
                            'title'   => ucfirst($extracted['action'] ?? 'Task') . ' Request',
                            'content' => $extracted['content'] ?? $extracted['description'] ?? $input,
                            'message' => $input,
                        ]
                    ),
                ];
            }
        }

        // Safe explicit fallback for unstructured inputs
        return [
            'table'   => 'queries',
            'action'  => 'generic_query',
            'payload' => [
                'title'   => 'Unstructured Prompt',
                'message' => $input,
                'content' => $input,
                'status'  => 'pending',
            ],
        ];
    }

    /**
     * Extracts semantics from raw string and persists directly to target DB schema.
     */
    public function convertAndSave(string $text, Db $db): int|bool
    {
        $parsed = $this->read($text);
        return $db->save($parsed['table'], $parsed['payload']);
    }
}