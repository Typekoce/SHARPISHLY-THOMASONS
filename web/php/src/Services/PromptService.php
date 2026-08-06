<?php

namespace App\Services;

class PromptService
{
    private const ACTION_ROUTES = [
        'send_sms'     => ['action' => 'DISPATCH_SMS', 'table' => 'outbound_sms'],
        'send_email'   => ['action' => 'DISPATCH_EMAIL', 'table' => 'outbound_emails'],
        'create_agent' => ['action' => 'SPAWN_AGENT', 'table' => 'agent_tasks'],
    ];

    public function read(string $rawInstruction): array
    {
        $sentences = explode('.', $rawInstruction);
        $payload = [];
        $resolvedTable = 'queries';
        $resolvedAction = 'GENERIC_QUERY';

        foreach ($sentences as $index => $sentence) {
            $cleaned = trim($sentence);
            if (empty($cleaned)) {
                continue;
            }

            $parsed = $this->parseRecursive($cleaned);

            // Normalize action and update table routing if a matching route exists
            if (isset($parsed['action_raw']) && isset(self::ACTION_ROUTES[$parsed['action_raw']])) {
                $route = self::ACTION_ROUTES[$parsed['action_raw']];
                $parsed['action'] = $route['action'];
                $resolvedAction = $route['action'];
                $resolvedTable = $route['table'];
                unset($parsed['action_raw']);
            }

            $payload['step_' . ($index + 1)] = $parsed;
        }

        return [
            'table'   => $resolvedTable,
            'action'  => $resolvedAction,
            'payload' => !empty($payload) ? $payload : ['raw' => $rawInstruction],
            'status'  => 'pending'
        ];
    }

    private function parseRecursive(string $text, array $accumulator = []): array
    {
        $text = trim($text);
        if (empty($text)) {
            return $accumulator;
        }

        // Action Extraction
        if (!isset($accumulator['action_raw']) && preg_match('/\b(send|dispatch|transmit)\s+(sms|text|message|email)\b/i', $text, $matches)) {
            $accumulator['action_raw'] = strtolower($matches[1]) . '_' . strtolower($matches[2]);
            $accumulator['channel'] = strtoupper($matches[2]);
            $remaining = trim(preg_replace('/' . preg_quote($matches[0], '/') . '/i', '', $text, 1));
            return $this->parseRecursive($remaining, $accumulator);
        }

        // Sentiment & Preference Extraction
        if (!isset($accumulator['preference']) && preg_match('/^(.+?)\s+is\s+not\s+(?:my\s+)?favourite\s+(.+)$/i', $text, $matches)) {
            $accumulator['preference'] = [
                'subject'   => trim($matches[1]),
                'category'  => trim($matches[2]),
                'sentiment' => 'negative'
            ];
            $remaining = trim(str_replace($matches[0], '', $text));
            return $this->parseRecursive($remaining, $accumulator);
        }

        // Clause Splitting
        if (preg_match('/^([^,]+),\s*(.+)$/i', $text, $matches)) {
            $accumulator = $this->parseRecursive($matches[1], $accumulator);
            return $this->parseRecursive($matches[2], $accumulator);
        }

        if (!empty($text) && !isset($accumulator['raw_segment'])) {
            $accumulator['raw_segment'] = $text;
        }

        return $accumulator;
    }
}