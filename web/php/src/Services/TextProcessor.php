<?php
declare(strict_types=1);

namespace App\Services;

class TextProcessor
{
    /**
     * Prepare CSV cell/row content for embedding:
     *  - Clean text
     *  - Add context metadata as prefix
     *  - Chunk only if extremely long
     *
     * @param string $content Raw cell or concatenated row content
     * @param array  $meta    e.g. ['source' => 'sales.csv', 'row' => 42, 'column' => 'description']
     * @return array<string>  Usually 1 item; multiple only if chunked
     */
    public function prepare(string $content, array $meta = []): array
    {
        $clean = $this->clean($content);

        if (mb_strlen($clean) < 8) {
            return []; // skip near-empty garbage
        }

        // Build context prefix
        $prefixParts = [];
        foreach ($meta as $k => $v) {
            $prefixParts[] = ucfirst((string)$k) . ': ' . (string)$v;
        }
        $prefix = implode(' | ', $prefixParts);

        $final = trim($prefix ? "$prefix | $clean" : $clean);

        // Most CSV rows are short → no chunking needed
        if (mb_strlen($final) <= 1200) {
            return [$final];
        }

        // Rare case: very long cell → chunk with overlap
        return $this->chunk($final, 500, 80);
    }

    private function clean(string $text): string
    {
        $text = strip_tags($text);

        // Remove URLs
        $text = preg_replace(
            '#\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]#i',
            '',
            $text
        );

        // Keep letters, numbers, basic punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s\-.,!?\'"…]/u', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim(mb_strtolower($text, 'UTF-8'));
    }

    /**
     * Split long text into overlapping chunks (word-aware)
     */
    public function chunk(string $text, int $maxWords = 500, int $overlapWords = 80): array
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];

        for ($i = 0; $i < count($words); $i += $maxWords - $overlapWords) {
            $slice = array_slice($words, $i, $maxWords);
            $chunks[] = implode(' ', $slice);

            // Stop if we've covered everything
            if ($i + $maxWords >= count($words)) {
                break;
            }
        }

        return $chunks;
    }
}