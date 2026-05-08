<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use UnexpectedValueException;
use SplFileObject;
use Throwable;

class FileStaging
{
    /**
     * Extracts meaningful text units from supported file types.
     * Returns array of strings — each ready for TextProcessor.
     *
     * @throws RuntimeException if file missing or unreadable
     * @throws UnexpectedValueException if format unsupported or corrupted
     */
    public function stage(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("Cannot read file for staging: $filePath");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv'  => $this->parseCsv($filePath),
            'txt', 'text', 'md' => $this->parsePlainText($filePath),
            default => throw new UnexpectedValueException("Unsupported file type for staging: .$extension"),
        };
    }

    /**
     * Stream CSV rows, detect delimiter automatically, join columns → single text string per row
     */
    private function parseCsv(string $path): array
    {
        $rows = [];

        try {
            $file = new SplFileObject($path, 'r');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

            // Read first row to detect delimiter
            $firstRow = $file->fgetcsv();
            if ($firstRow === false || $firstRow === [null]) {
                throw new UnexpectedValueException("Empty or invalid CSV file");
            }

            // Reset file pointer
            $file->rewind();

            // Guess best delimiter from first row
            $delimiter = $this->detectCsvDelimiter($file->current() ?? []);
            $file->setCsvControl($delimiter);

            // Process all rows
            foreach ($file as $row) {
                if (!is_array($row) || count($row) === 0) {
                    continue;
                }

                // Filter empty cells, join with space
                $text = implode(' ', array_filter($row, static fn($v) => trim((string)$v) !== ''));

                if (trim($text) !== '') {
                    $rows[] = $text;
                }
            }
        } catch (Throwable $e) {
            throw new UnexpectedValueException("Failed to parse CSV: " . $e->getMessage(), 0, $e);
        }

        return $rows;
    }

    /**
     * Simple but effective delimiter detection based on first row
     */
    private function detectCsvDelimiter(array $firstRow): string
    {
        if (count($firstRow) <= 1) {
            return ','; // fallback
        }

        $candidates = [',', ';', "\t", '|', ':'];
        $scores = [];

        foreach ($candidates as $delim) {
            $count = 0;
            foreach ($firstRow as $cell) {
                $count += substr_count((string)$cell, $delim);
            }
            $scores[$delim] = $count;
        }

        arsort($scores);
        $best = key($scores);

        // Require at least some occurrences and reasonable field count
        if ($scores[$best] > 0 && count($firstRow) > 2) {
            return $best;
        }

        return ','; // safest default
    }

    /**
     * Parse plain text file — split by double newlines (paragraphs)
     * Falls back to single newlines if no double newlines found
     */
    private function parsePlainText(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read text file: $path");
        }

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Try paragraph split first
        $paragraphs = array_filter(
            array_map('trim', explode("\n\n", $content)),
            static fn($p) => $p !== ''
        );

        // If almost no paragraphs → fall back to line-by-line
        if (count($paragraphs) <= 1 && str_contains($content, "\n")) {
            $paragraphs = array_filter(
                array_map('trim', explode("\n", $content)),
                static fn($line) => $line !== ''
            );
        }

        return $paragraphs;
    }
}