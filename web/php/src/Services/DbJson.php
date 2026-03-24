<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use RuntimeException;

/**
 * DbJson - File-based mock database for development & testing
 * Replaces real PDO when MySQL networking is unreliable.
 * Persists data to storage/database/db.json
 */
class DbJson
{
    private string $filePath;
    private array $data = [
        'tables' => [],       // actual table data: ['jobs' => [...rows...], 'migrations' => [...]]
        'sequences' => [],    // auto-increment counters: ['jobs' => 5, 'migrations' => 3]
        'log' => []           // query log
    ];

    public function __construct()
    {
        $this->filePath = rtrim(APP_ROOT, '/') . '/storage/database/db.json';

        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                throw new RuntimeException("Cannot create mock DB directory: $dir");
            }
        }

        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            $this->data = json_decode($content, true) ?: $this->data;
        } else {
            $this->save();
        }
    }

    private function save(): void
    {
        file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Log a "query" (for debugging)
     */
    private function logQuery(string $sql, array $params = []): void
    {
        $this->data['log'][] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'query'     => $sql,
            'params'    => $params,
        ];
        // Keep log to last 100 entries
        if (count($this->data['log']) > 100) {
            $this->data['log'] = array_slice($this->data['log'], -100);
        }
        $this->save();
    }

    /**
     * Execute a "query" (migrations, CREATE TABLE, UPDATE, INSERT, etc.)
     */
    public function execute(string $sql, array $params = []): bool
    {
        $this->logQuery($sql, $params);

        // Very basic parsing for supported operations
        $upper = strtoupper(trim($sql));

        if (str_starts_with($upper, 'CREATE TABLE')) {
            preg_match('/CREATE TABLE IF NOT EXISTS `?([^` ]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            if ($table && !isset($this->data['tables'][$table])) {
                $this->data['tables'][$table] = [];
                $this->data['sequences'][$table] = 0;
                $this->save();
            }
            return true;
        }

        if (str_starts_with($upper, 'INSERT INTO')) {
            preg_match('/INSERT INTO `?([^` ]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            if (!$table || !isset($this->data['tables'][$table])) {
                throw new Exception("Table '$table' does not exist in mock DB");
            }

            // Very simplistic: assume VALUES (?,?,?) style
            $id = ++$this->data['sequences'][$table];
            $row = ['id' => $id] + array_combine(range(1, count($params)), $params);
            $this->data['tables'][$table][] = $row;
            $this->save();
            return true;
        }

        if (str_starts_with($upper, 'UPDATE')) {
            preg_match('/UPDATE `?([^` ]+)`? SET/i', $sql, $m);
            $table = $m[1] ?? null;
            if (!$table || !isset($this->data['tables'][$table])) {
                throw new Exception("Table '$table' does not exist in mock DB");
            }

            // Very limited: only supports WHERE id = ?
            if (preg_match('/WHERE id = \?/i', $sql)) {
                $id = (int)($params[0] ?? 0);
                foreach ($this->data['tables'][$table] as &$row) {
                    if (($row['id'] ?? 0) === $id) {
                        // Apply updates (very naive – assumes params after WHERE)
                        $updates = array_slice($params, 1);
                        // You'd need real parser here in real life – this is demo only
                        $row['updated_at'] = date('c'); // example
                        $this->save();
                        return true;
                    }
                }
            }
            return true; // optimistic
        }

        // Default: assume success for unsupported ops (migrations, etc.)
        return true;
    }

    /**
     * Prepare → execute → fetchAll (for SELECT)
     */
    public function query(string $sql, array $params = []): array
    {
        $this->logQuery($sql, $params);

        $upper = strtoupper(trim($sql));

        if (str_starts_with($upper, 'SELECT')) {
            preg_match('/FROM `?([^` ]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;

            if (!$table || !isset($this->data['tables'][$table])) {
                return [];
            }

            $rows = $this->data['tables'][$table];

            // Very basic WHERE id = ?
            if (preg_match('/WHERE id = \?/i', $sql)) {
                $id = (int)($params[0] ?? 0);
                foreach ($rows as $row) {
                    if (($row['id'] ?? 0) === $id) {
                        return [$row];
                    }
                }
                return [];
            }

            // Default: return all
            return $rows;
        }

        return [];
    }

    public function lastInsertId(): string
    {
        // Returns last auto-increment value (simplified)
        return (string)max($this->data['sequences'] ?? [0]);
    }

    /**
     * Quick debug helper – view all stored data
     */
    public function dump(): array
    {
        return $this->data;
    }
}