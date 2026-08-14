<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Orm;

class UnificationGateway
{
    /**
     * Dispatch concurrent API requests and synthesize with LLM context.
     */
    public function query(string $prompt, array $sources): array
    {
        $mh = curl_multi_init();
        $handles = [];

        // 1. Fire off requests concurrently
        foreach ($sources as $key => $config) {
            $ch = curl_init($config['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $config['headers'] ?? [],
                CURLOPT_TIMEOUT        => 3,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        // 2. Non-blocking execution loop
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // 3. Harvest results
        $context = [];
        foreach ($handles as $key => $ch) {
            $raw = curl_multi_getcontent($ch);
            $context[$key] = json_decode((string)$raw, true) ?? ['raw' => $raw];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // 4. Send context directly to ORM / LLM
        $orm = new Orm();
        return $orm->execute([
            'source' => 'Ollama',
            'action' => 'create',
            'data'   => [
                'model'  => 'llama3',
                'prompt' => "User Prompt: {$prompt}\n\nContext Data:\n" . json_encode($context),
            ],
        ]);
    }
}