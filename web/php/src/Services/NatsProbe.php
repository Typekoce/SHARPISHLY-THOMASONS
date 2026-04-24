<?php
declare(strict_types=1);

namespace App\Services;

/**
 * NatsProbe Service
 * Implements Perplexity-approved Request/Reply for liveness checks.
 */
class NatsProbe {
    public static function checkWorker($nats): array {
        try {
            // timeout is 500ms to keep the UI snappy
            $response = $nats->request('heartbeat.python', '{"probe": "ping"}', 500);
            
            return [
                'active' => true,
                'data'   => json_decode($response->getBody(), true)
            ];
        } catch (\Throwable $e) {
            return [
                'active' => false,
                'error'  => 'Worker Offline: ' . $e->getMessage()
            ];
        }
    }
}
