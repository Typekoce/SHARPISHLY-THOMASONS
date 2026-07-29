<?php

namespace App\Models;

class IngestionModel
{
    /**
     * Executes fetch with automatic RSS fallback for anti-bot protected targets.
     *
     * @param string $url
     * @return string|false
     */
    public function fetchRaw(string $url)
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Strategy 3: Auto-convert web URLs to RSS endpoint to bypass Cloudflare protection
        if (strpos($url, 'indeed.com') !== false && strpos($url, '/rss') === false) {
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $queryParams);

            $url = 'https://uk.indeed.com/rss?' . http_build_query([
                'q' => $queryParams['q'] ?? 'software developer',
                'l' => $queryParams['l'] ?? '',
            ]);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-GB,en;q=0.9',
            ],
            CURLOPT_ENCODING       => '',
        ]);

        $content = curl_exec($ch);
        $errno   = curl_errno($ch);
        $status  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || empty($content) || $status >= 400) {
            return false;
        }

        return $content;
    }
}