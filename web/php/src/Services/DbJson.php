<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use Exception;
use RuntimeException;

/**
 * DbJson - File-based mock database for development & testing.
 * Updated to use centralized App\Core\Registry and root /storage pathing.
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
        // 1. Resolve path via the registered Location service
        // We use get() because bootstrap.php already instantiated Location
        $location = Registry::get(Location::class);
        $this->filePath = $location->db('db.json');

        // 2. Ensure the directory exists
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            // Using 0777 for dev environment to ensure Docker/Nginx can write
            // but we use @ to suppress warnings if another process creates it simultaneously
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException("Cannot create mock DB directory: $dir. Check root permissions.");
            }
        }

        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            try {
                $this->data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                error_log("DbJson Load Error: " . $e->getMessage());
                // Fallback to default structure if file is corrupt
            }
        } else {
            $this->save();
        }
    }

    private function save(): void
    {
        // Use LOCK_EX to prevent corruption during simultaneous Agent writes
        file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            LOCK_EX
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

    public function execute(string $sql, array $params = []): bool
    {
        $this->logQuery($sql, $params);
        $upper = strtoupper(trim($sql));

        if (str_contains($upper, 'CREATE TABLE')) {
            preg_match('/CREATE TABLE IF NOT EXISTS `?([^` \(]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            if ($table && !isset($this->data['tables'][$table])) {
                $this->data['tables'][$table] = [];
                $this->data['sequences'][$table] = 0;
                $this->save();
            }
            return true;
        }

        if (str_starts_with($upper, 'INSERT INTO')) {
            preg_match('/INSERT INTO `?([^` \(]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            if (!$table || !isset($this->data['tables'][$table])) {
                // If table doesn't exist, we auto-create it for the Mock DB (Dev convenience)
                $this->data['tables'][$table] = [];
                $this->data['sequences'][$table] = 0;
            }

            $id = ++$this->data['sequences'][$table];
            $row = ['id' => $id] + $params; // Simpler mapping for mock payloads
            $this->data['tables'][$table][] = $row;
            $this->save();
            return true;
        }

        return true;
    }

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

            return $this->data['tables'][$table];
        }

        return [];
    }

    public function lastInsertId(): string
    {
        return (string)max($this->data['sequences'] ?: [0]);
    }

    public function dump(): array
    {
        return $this->data;
    }
}