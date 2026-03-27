<?php
declare(strict_types=1);

namespace App\Services;

class TextProcessor
{
    /**
     * Entry point for the worker.
     * Cleans text and adds context metadata as a prefix.
     */
    public function prepare(string $content, array $meta = []): string
    {
        $clean = $this->clean($content);

        if (mb_strlen($clean) < 8) {
            return ""; // Skip near-empty garbage
        }

        // Build context prefix: "Source: nike.csv | Row: 42 | ..."
        $prefixParts = [];
        foreach ($meta as $k => $v) {
            $prefixParts[] = ucfirst((string)$k) . ': ' . (string)$v;
        }
        
        $prefix = implode(' | ', $prefixParts);
        return trim($prefix ? "$prefix | $clean" : $clean);
    }

    /**
     * Sanitizes raw string data.
     */
    public function clean(string $text): string
    {
        $text = strip_tags($text);

        // FIXED: Using # instead of / to avoid URL conflicts
        $urlPattern = '#\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]#i';
        
        // Use @ to suppress any remaining warnings and force a string return
        $text = (string)@preg_replace($urlPattern, '', $text);
        $text = (string)@preg_replace('#[^\p{L}\p{N}\s\-.,!?\'"…]#u', '', $text);
        $text = (string)@preg_replace('#\s+#u', ' ', $text);

        return trim(mb_strtolower($text, 'UTF-8'));
    }

    /**
     * Split long text into overlapping chunks (word-aware).
     * Returns an array of strings.
     */
    public function chunk(string $text, int $maxWords = 500, int $overlapWords = 80): array
    {
        if (empty($text)) {
            return [];
        }

        $words = preg_split('#\s+#', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) return [$text];

        $chunks = [];
        $totalWords = count($words);
        $step = $maxWords - $overlapWords;

        // Ensure we don't get stuck in an infinite loop if overlap >= max
        if ($step <= 0) $step = $maxWords;

        for ($i = 0; $i < $totalWords; $i += $step) {
            $slice = array_slice($words, $i, $maxWords);
            $chunks[] = implode(' ', $slice);

            if ($i + $maxWords >= $totalWords) {
                break;
            }
        }

        return $chunks;
    }
}