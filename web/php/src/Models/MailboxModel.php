<?php

namespace App\Models;

class MailboxModel extends BaseModel {

    /**
     * Executes Himalaya CLI and parses the resulting output into an array.
     */
    public function getInbox(): array {
        // Execute the command (ensure Himalaya is in your system PATH)
        $rawOutput = shell_exec('himalaya envelope list');
        
        if (!$rawOutput) {
            return [];
        }

        return $this->parseHimalayaLog($rawOutput);
    }

    /**
     * Parses Himalaya CLI log into a clean associative array with robustness guards.
     */
    public function parseHimalayaLog(string $logContent): array {
        // Strip ANSI escape codes
        $cleanContent = preg_replace('/\x1B\[[0-9;]*[mK]/', '', $logContent);
        $lines = explode("\n", $cleanContent);
        
        $parsedData = [];
        $headers = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip non-table lines
            if (empty($line) || $line[0] !== '|') continue;
            
            // Skip table separator rows (e.g., |----|-------|)
            if (preg_match('/^\|[\-\s\|]+\|$/', $line)) continue;

            // Extract columns and trim whitespace
            $columns = array_map('trim', explode('|', trim($line, '|')));

            // Set headers from the first valid row
            if (empty($headers)) {
                $headers = array_map('strtolower', $columns);
                continue;
            } 
            
            // Apply robustness guard: count match before array_combine
            if (count($columns) === count($headers)) {
                $row = array_combine($headers, $columns);
                if ($row !== false) {
                    $parsedData[] = $row;
                }
            }
        }

        return $parsedData;
    }
}