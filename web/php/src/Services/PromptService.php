<?php

declare(strict_types=1);

namespace App\Services;

use JsonException;

final class PromptService
{
    private array $lastDiagnostics = [
        'status' => 'unknown',
        'mocked' => false,
        'code' => null
    ];

    public function __construct(
        private string $ragUrl = 'http://127.0.0.1:8765/rag/ask',
        private int $timeoutSeconds = 2
    ) {
    }

    public function getLastDiagnostics(): array
    {
        return $this->lastDiagnostics;
    }

    /**
     * Parse raw instruction text into a structured prompt payload.
     */
    public function read(string $rawInstruction): array
    {
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

            $segments = preg_split('/[,;:!?\n]+/', $cleanedSentence, -1, PREG_SPLIT_NO_EMPTY);
            $sentencePayload = [];

            foreach ($segments as $clauseIndex => $segment) {
                $cleanedSegment = trim($segment);
                if ($cleanedSegment === '') {
                    continue;
                }

                $analysis = $this->analyzeSegment($cleanedSegment);

                if (isset($analysis['action_raw'])) {
                    $resolvedAction = 'DISPATCH_ACTION';
                    $resolvedTable  = 'actions';
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

    private function analyzeSegment(string $segment): array
    {
        return [
            'raw' => $segment,
            'nlp' => [
                'token_count' => count(explode(' ', $segment)),
            ]
        ];
    }

    /**
     * Converts a prompt instruction into ORM condition key-value pairs.
     */
    public function promptToConditions(string $prompt = ''): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            $this->lastDiagnostics = [
                'status' => 'empty',
                'mocked' => false,
                'code' => null
            ];
            return [];
        }

        $conditions = [
            'status' => 'pending'
        ];

        $parsedPrompt = $this->read($prompt);

        if (
            isset($parsedPrompt['action']) &&
            $parsedPrompt['action'] !== 'GENERIC_QUERY'
        ) {
            $conditions['action'] = $parsedPrompt['action'];
        }

        $ragResult = $this->queryRag($prompt);

        // Store explicit diagnostics separately for workflow validation
        $this->lastDiagnostics = $ragResult['diagnostics'];
        $ragData = $ragResult['data'];

        $conditions['rag_service_status'] = $this->lastDiagnostics['status'];
        $conditions['rag_mocked'] = $this->lastDiagnostics['mocked'];
        $conditions['rag_diagnostic'] = $this->lastDiagnostics['code'];

        if (!empty($ragData['filters'])) {
            $conditions = array_merge($conditions, $ragData['filters']);
        }

        if (!empty($ragData['answer'])) {
            $conditions['rag_context'] = $ragData['answer'];
        }

        return $conditions;
    }

    /**
     * Build structured prompt context for application tailoring.
     */
    public function buildContext(array $context): array
    {
        $vacancy = $context['vacancy'] ?? [];
        $sourceCv = $context['source_cv'] ?? [];

        $vacancyTitle = $vacancy['title'] ?? 'Target Role';
        $company = $vacancy['company'] ?? 'Target Company';
        $description = $vacancy['description'] ?? '';

        $promptText = "Tailor the primary CV for the position of {$vacancyTitle} at {$company}. Description: {$description}";

        $parsed = $this->read($promptText);
        $conditions = $this->promptToConditions($promptText);
        $diagnostics = $this->getLastDiagnostics();

        return [
            'prompt_text' => $promptText,
            'parsed_instructions' => $parsed,
            'vacancy' => $vacancy,
            'source_cv' => $sourceCv,
            'rag_conditions' => $conditions,
            'generation_mode' => $diagnostics['mocked'] ? 'mocked' : 'live',
            'rag_status' => $diagnostics['status'],
            'rag_diagnostic_code' => $diagnostics['code']
        ];
    }

    private function queryRag(string $prompt): array
    {
        $url = $this->ragUrl . '?' . http_build_query([
            'query' => $prompt
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'max_redirects' => 0,
                'header' => [
                    'Accept: application/json',
                    'Connection: close'
                ]
            ]
        ]);

        $rawResponse = @file_get_contents($url, false, $context);
        $statusCode = $this->responseStatusCode($http_response_header ?? []);

        if ($rawResponse === false) {
            $diagnostic = 'connection_failed';
            $this->logRagFailure($diagnostic, $url, $statusCode, $prompt);
            return [
                'data' => [
                    'answer' => 'Mock RAG response: tailoring application profile for web design and frontend integration.',
                    'filters' => [
                        'service_status' => 'offline_mocked',
                        'target_focus' => 'web_design'
                    ]
                ],
                'diagnostics' => [
                    'status' => 'offline_mocked',
                    'mocked' => true,
                    'code' => $diagnostic
                ]
            ];
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $diagnostic = 'http_' . ($statusCode ?: 'unknown');
            $this->logRagFailure($diagnostic, $url, $statusCode, $prompt, $rawResponse);
            return [
                'data' => [
                    'answer' => 'Mock RAG response: tailoring application profile for web design and frontend integration.',
                    'filters' => [
                        'service_status' => 'error_mocked',
                        'target_focus' => 'web_design'
                    ]
                ],
                'diagnostics' => [
                    'status' => 'error_mocked',
                    'mocked' => true,
                    'code' => $diagnostic
                ]
            ];
        }

        try {
            $ragData = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logRagFailure('invalid_json', $url, $statusCode, $prompt, $e->getMessage());
            return [
                'data' => [
                    'answer' => 'Mock RAG response: tailoring application profile for web design and frontend integration.',
                    'filters' => [
                        'service_status' => 'invalid_response_mocked',
                        'target_focus' => 'web_design'
                    ]
                ],
                'diagnostics' => [
                    'status' => 'invalid_response_mocked',
                    'mocked' => true,
                    'code' => 'invalid_json'
                ]
            ];
        }

        if (!is_array($ragData)) {
            $this->logRagFailure('response_not_object', $url, $statusCode, $prompt);
            return [
                'data' => [
                    'answer' => 'Mock RAG response: tailoring application profile for web design and frontend integration.',
                    'filters' => [
                        'service_status' => 'invalid_response_mocked',
                        'target_focus' => 'web_design'
                    ]
                ],
                'diagnostics' => [
                    'status' => 'invalid_response_mocked',
                    'mocked' => true,
                    'code' => 'response_not_object'
                ]
            ];
        }

        return [
            'data' => [
                'answer' => isset($ragData['answer']) && is_string($ragData['answer']) ? trim($ragData['answer']) : '',
                'filters' => isset($ragData['filters']) && is_array($ragData['filters']) ? $ragData['filters'] : []
            ],
            'diagnostics' => [
                'status' => 'online',
                'mocked' => false,
                'code' => null
            ]
        ];
    }

    private function responseStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }
        return 0;
    }

    private function logRagFailure(string $diagnostic, string $url, int $statusCode, string $prompt, string $detail = ''): void
    {
        // Protect personal data by hashing prompts and truncating raw previews instead of logging full text
        error_log("[PromptService] RAG fallback activated " . json_encode([
            'diagnostic' => $diagnostic,
            'url' => $url,
            'status_code' => $statusCode,
            'prompt_length' => strlen($prompt),
            'prompt_hash' => hash('sha256', $prompt),
            'detail_preview' => substr($detail, 0, 200)
        ], JSON_UNESCAPED_SLASHES));
    }
}