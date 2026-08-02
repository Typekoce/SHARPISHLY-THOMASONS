<?php

declare(strict_types=1);

namespace App\Services;

class AgentExecutor
{
    /**
     * Executes a stored agent plan deterministically
     */
    public function execute(array $agent): bool
    {
        $plan = json_decode($agent['content'] ?? '', true);

        if (!is_array($plan) || empty($plan['steps']) || !is_array($plan['steps'])) {
            return false;
        }

        foreach ($plan['steps'] as $step) {
            $action = $step['action'] ?? '';
            $params = $step['params'] ?? [];

            $this->dispatch($action, $params);
        }

        return true;
    }

    /**
     * Map plan actions directly to system capabilities
     */
    private function dispatch(string $action, array $params): void
    {
        switch ($action) {
            case 'filter_number':
                // $this->telephonyService->filterNumber($params);
                break;

            case 'transcribe_voicemail':
                // $this->telephonyService->transcribeVoicemail($params);
                break;

            case 'log_activity':
            default:
                if (function_exists('App\log')) {
                    \App\log("Agent step executed: {$action}", $params);
                }
                break;
        }
    }
}
