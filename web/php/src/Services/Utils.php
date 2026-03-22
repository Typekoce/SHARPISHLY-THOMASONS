<?php

namespace App\Services;

class Utils {

    public function __construct(){

    }

    /**
     * Cleans text for embedding / search / logging purposes:
     * - Removes HTML/PHP tags
     * - Strips URLs
     * - Keeps only letters, numbers, basic punctuation & whitespace
     * - Normalizes case and whitespace
     */
    function cleanText(string $text): string
    {
        // 1. Strip all HTML/PHP tags
        $text = strip_tags($text);

        // 2. Remove URLs (more robust pattern)
        $text = preg_replace(
            '#\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]#i',
            '',
            $text
        );

        // 3. Keep only unicode letters/numbers + basic punctuation + space
        //    (allows -, ', ", ., ,, !, ?, …)
        $text = preg_replace('/[^\p{L}\p{N}\s\-.,!?\'"…]/u', '', $text);

        // 4. Collapse all whitespace (including newlines, tabs) → single space
        $text = preg_replace('/\s+/u', ' ', $text);

        // 5. Trim + lowercase (mbstring safe)
        return trim(mb_strtolower($text, 'UTF-8'));
    }    

}