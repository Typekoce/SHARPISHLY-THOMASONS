<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use Exception;
use RuntimeException;

/**
 * DbJson - File-based mock database for development & testing
 * Replaces real PDO when MySQL networking is unreliable.
 * Persists data to the path provided by the Location service.
 */
class DbJson
{
    private string $filePath;
    private array $data = [
        'tables' => [],       
        'sequences' => [],    
        'log' => []           
    ];

    public function __construct()
    {
        // 1. Resolve path via the centralized Location service
        $location = Registry::make(Location::class);
        $this->filePath = $location->db('db.json');

        // 2. Ensure the directory exists with proper permissions
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            // Using 0777 for dev environment to avoid Docker mount permission collisions
            if (!mkdir($dir, 0777, true)) {
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

            if (preg_match('/WHERE id = \?/i', $sql)) {
                $id = (int)($params[0] ?? 0);
                foreach ($this->data['tables'][$table] as &$row) {
                    if (($row['id'] ?? 0) === $id) {
                        $row['updated_at'] = date('c'); 
                        $this->save();
                        return true;
                    }
                }
            }
            return true; 
        }

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

            if (preg_match('/WHERE id = \?/i', $sql)) {
                $id = (int)($params[0] ?? 0);
                foreach ($rows as $row) {
                    if (($row['id'] ?? 0) === $id) {
                        return [$row];
                    }
                }
                return [];
            }

            return $rows;
        }

        return [];
    }

    public function lastInsertId(): string
    {
        return (string)max($this->data['sequences'] ?? [0]);
    }

    public function dump(): array
    {
        return $this->data;
    }
}