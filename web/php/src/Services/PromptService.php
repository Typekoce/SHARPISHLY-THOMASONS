<?php

declare(strict_types=1);

namespace App\Services;

class PromptService
{
    private const ACTION_ROUTES = [
        'send_sms'     => ['action' => 'DISPATCH_SMS',   'table' => 'outbound_sms'],
        'send_email'   => ['action' => 'DISPATCH_EMAIL', 'table' => 'outbound_emails'],
        'create_agent' => ['action' => 'SPAWN_AGENT',    'table' => 'agent_tasks'],
    ];

    /**
     * Parse raw instruction text into a structured prompt payload.
     */
    public function read(string $rawInstruction): array
    {
        // 1. Split by sentence (periods)
        $sentences = array_filter(explode('.', $rawInstruction), static fn($s) => trim($s) !== '');
        $payload = [];
        $resolvedTable  = 'queries';
        $resolvedAction = 'GENERIC_QUERY';

        $sentenceIndex = 1;
        foreach ($sentences as $sentence) {
            $cleanedSentence = trim($sentence);
            if ($cleanedSentence === '') {
                continue;
            }

            // 2. Split sentence by clause delimiters
            $segments = preg_split('/[,;:!?\n]+/', $cleanedSentence, -1, PREG_SPLIT_NO_EMPTY);
            $sentencePayload = [];

            foreach ($segments as $clauseIndex => $segment) {
                $cleanedSegment = trim($segment);
                if ($cleanedSegment === '') {
                    continue;
                }

                // 3. Analyze segment (regex, logic, semantic, NLP)
                $analysis = $this->analyzeSegment($cleanedSegment);

                // Update route resolution if an action trigger is detected
                if (isset($analysis['action_raw'], self::ACTION_ROUTES[$analysis['action_raw']])) {
                    $route = self::ACTION_ROUTES[$analysis['action_raw']];
                    $resolvedAction = $route['action'];
                    $resolvedTable  = $route['table'];
                    $analysis['action'] = $route['action'];
                    unset($analysis['action_raw']);
                }

                $sentencePayload['clause_' . ($clauseIndex + 1)] = $analysis;
            }

            if (!empty($sentencePayload)) {
                $payload['sentence_' . $sentenceIndex] = $sentencePayload;
                $sentenceIndex++;
            }
        }

        return [
            'table'   => $resolvedTable,
            'action'  => $resolvedAction,
            'payload' => $payload ?: ['raw' => $rawInstruction],
            'status'  => 'pending',
        ];
    }

    /**
     * Executes segment-level processing pipeline across regex, logic, semantic, and NLP rules.
     */
    private function analyzeSegment(string $segment): array
    {
        $analysis = [
            'raw' => $segment,
        ];

        // A. Regex Parsing (Action & Dispatch Extraction)
        if (preg_match('/\b(send|dispatch|transmit)\s+(sms|text|message|email)\b/i', $segment, $matches)) {
            $analysis['action_raw'] = strtolower($matches[1]) . '_' . strtolower($matches[2]);
            $analysis['channel']    = strtoupper($matches[2]);
        }

        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $segment, $matches)) {
            $analysis['entity_email'] = $matches[0];
        }

        // B. Logic & Sentiment Rules
        if (preg_match('/^(.+?)\s+is\s+not\s+(?:my\s+)?favourite\s+(.+)$/i', $segment, $matches)) {
            $analysis['logic'] = [
                'rule'      => 'PREFERENCE_DISLIKE',
                'subject'   => trim($matches[1]),
                'category'  => trim($matches[2]),
                'sentiment' => 'negative',
            ];
        }

        // C. Semantic & NLP (Unicode-safe Tokenization & Cleaning)
        $normalized = strtolower(preg_replace('/[^\w\s]/u', '', $segment));
        $tokens     = array_values(array_filter(explode(' ', $normalized), static fn($t) => $t !== ''));

        $analysis['nlp'] = [
            'tokens'      => $tokens,
            'token_count' => count($tokens),
        ];

        return $analysis;
    }
}