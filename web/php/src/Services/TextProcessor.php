<?php
declare(strict_types=1);

namespace App\Services;

/**
 * TextProcessor - Neural Data Sanitizer
 * Pivot Strategy: Whitelist characters over complex regex blacklisting.
 */
class TextProcessor
{
    /**
     * Entry point for the worker.
     * Cleans text and adds context metadata as a prefix.
     */
    public function prepare(string $content, array $meta = []): string
    {
        $this->log('Neural Processing: Preparing content for embedding');

        $clean = $this->clean($content);

        if (mb_strlen($clean) < 8) {
            $this->log('Scrubbed: Content too short after cleaning');
            return ""; 
        }

        // Build context prefix: "Source: nike.csv | Row: 42"
        $prefixParts = [];
        foreach ($meta as $k => $v) {
            $prefixParts[] = ucfirst((string)$k) . ': ' . (string)$v;
        }
        
        $prefix = implode(' | ', $prefixParts);
        $final = trim($prefix ? "$prefix | $clean" : $clean);

        $this->log('Preparation complete');
        return $final;
    }

    /**
     * Sanitizes raw string data.
     * Pivot: Character-Whitelisting to avoid "Modifier" and "Delimiter" hell.
     */
    public function clean(string $text): string
    {
        $this->log('Regex for cleaning files: Starting sanitization');

        // 1. Strip HTML tags
        $text = strip_tags($text);

        // 2. Pivot: Remove URLs using safe # delimiters
        $urlPattern = '#\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]#i';
        $text = (string)@preg_replace($urlPattern, '', $text);

        // 3. Defensive: Keep only printable characters, letters, numbers, and basic punctuation
        // This is much safer than trying to "find and remove" specific bad characters.
        $text = (string)@preg_replace('#[^\p{L}\p{N}\s\-.,!?\'"…]#u', '', $text);

        // 4. Normalize whitespace
        $text = (string)@preg_replace('#\s+#u', ' ', $text);

        $this->log('Regex for cleaning files: Complete');

        return trim(mb_strtolower($text, 'UTF-8'));
    }

    /**
     * Split long text into overlapping chunks (word-aware).
     */
    public function chunk(string $text, int $maxWords = 500, int $overlapWords = 80): array
    {
        if (empty($text)) {
            return [];
        }

        // Split by any whitespace
        $words = @preg_split('#\s+#', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return [$text];
        }

        $chunks = [];
        $totalWords = count($words);
        $step = $maxWords - $overlapWords;

        // Infinite loop protection
        if ($step <= 0) {
            $step = $maxWords;
        }

        for ($i = 0; $i < $totalWords; $i += $step) {
            $slice = array_slice($words, $i, $maxWords);
            $chunks[] = implode(' ', $slice);

            if ($i + $maxWords >= $totalWords) {
                break;
            }
        }

        return $chunks;
    }

    /**
     * Placeholder for neural audit trail
     */
    private function log(string $message): void
    {
        // Integration point for Logger service if needed
        // error_log("[TextProcessor] $message");
    }
}