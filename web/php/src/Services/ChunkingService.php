<?php
declare(strict_types=1);

namespace App\Services;

class ChunkingService
{
    /**
     * Splits text into manageable chunks for vectorization.
     * * @param string $text The raw text from the document.
     * @param int $size Approximate character count per chunk.
     * @param int $overlap Number of characters to repeat in the next chunk.
     */
    public function split(string $text, int $size = 800, int $overlap = 150): array
    {
        // 1. Basic cleaning: Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $chunks = [];
        $start = 0;
        $textLength = mb_strlen($text);

        if ($textLength <= $size) {
            return [$text];
        }

        while ($start < $textLength) {
            // Take a slice of the text
            $chunk = mb_substr($text, $start, $size);

            // 2. "Soft Landing" - try to break at the end of a sentence/period
            // so we don't cut a property measurement or year in half.
            if ($start + $size < $textLength) {
                $lastPeriod = mb_strrpos($chunk, '. ');
                if ($lastPeriod !== false && $lastPeriod > ($size * 0.7)) {
                    $chunk = mb_substr($chunk, 0, $lastPeriod + 1);
                }
            }

            $chunks[] = trim($chunk);

            // Move the pointer forward, minus the overlap
            $start += (mb_strlen($chunk) - $overlap);

            // Safety break for very short remaining text
            if ($start >= $textLength - $overlap) {
                break;
            }
        }

        return array_filter($chunks);
    }
}