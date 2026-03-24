<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use RuntimeException;
use Throwable;

class Migrator
{
    private DbJson $db;
    private Location $loc;
    private string $migrationsDir;

    public function __construct()
    {
        // 'db' was set as a string in bootstrap Section 4
        $this->db  = Registry::get('db');
        
        // Location was set using the Class Name in my fix above
        $this->loc = Registry::get(Location::class);

        if (!$this->db || !$this->loc) {
            throw new \RuntimeException("Migrator failed: Core services (Location/Db) not found in Registry.");
        }
        
        $this->migrationsDir = $this->loc->baseDir() . 'database/migrations/';
    }

    public function run(): array
    {
        $results = [];
        
        // Get already executed migrations from our JSON store
        $executed = $this->getExecutedMigrations();
        $files = $this->getPendingMigrationFiles($executed);

        if (empty($files)) {
            return ['System is up to date. No new migrations found.'];
        }

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            try {
                // Since this is a Mock/JSON DB, we don't 'exec' the SQL.
                // We record the 'fact' that the migration ran.
                $this->recordMigration($filename);
                $results[] = "✅ Applied: $filename";
                
            } catch (Throwable $e) {
                $results[] = "❌ Failed: $filename - " . $e->getMessage();
                break; 
            }
        }

        return $results;
    }

    private function getExecutedMigrations(): array
    {
        // Query the 'migrations' key in our db.json
        $data = $this->db->query("migrations") ?? [];
        return array_column($data, 'migration');
    }

    private function getPendingMigrationFiles(array $executed): array
    {
        $allFiles = glob($this->migrationsDir . '*.sql');
        if ($allFiles === false) return [];
        
        sort($allFiles);

        return array_filter($allFiles, function ($file) use ($executed) {
            return !in_array(basename($file), $executed, true);
        });
    }

    private function recordMigration(string $filename): void
    {
        // Mock the insertion into the JSON structure
        $this->db->execute("INSERT INTO migrations (migration) VALUES (?)", [$filename]);
    }
}