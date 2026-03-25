<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use App\Services\Db; // Switch to the MySQL Service
use App\Services\Location;
use RuntimeException;
use Throwable;

class Migrator
{
    private $db; // Changed from DbJson to Db
    private Location $loc;
    private string $migrationsDir;

    public function __construct()
    {
        // Pull the service from Registry
        $this->db  = Registry::get('db');
        $this->loc = Registry::get(Location::class);

        if (!$this->db || !$this->loc) {
            throw new RuntimeException("Migrator failed: Core services (Location/Db) not found.");
        }
        
        // Ensure this matches your physical folder: infra/migrations/
        $this->migrationsDir = SRC_ROOT . '../infra/migrations/'; 
        
        // Ensure the migrations table exists in MySQL first
        $this->ensureMigrationTable();
    }

    public function run(): array
    {
        $results = [];
        $executed = $this->getExecutedMigrations();
        $files = $this->getPendingMigrationFiles($executed);

        if (empty($files)) {
            return ['System is up to date.'];
        }

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $sql = file_get_contents($filePath);

            try {
                // Execute the REAL SQL against MySQL
                $this->db->execute($sql);
                
                // Record the success
                $this->recordMigration($filename);
                $results[] = "✅ Applied: $filename";
                
            } catch (Throwable $e) {
                $results[] = "❌ Failed: $filename - " . $e->getMessage();
                break; 
            }
        }

        return $results;
    }

    private function ensureMigrationTable(): void
    {
        $this->db->createTable('migrations', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'migration' => 'VARCHAR(255) NOT NULL UNIQUE',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ]);
    }

    private function getExecutedMigrations(): array
    {
        $rows = $this->db->find(['tbl' => 'migrations']);
        return array_column($rows, 'migration');
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
        $this->db->save('migrations', ['migration' => $filename]);
    }
}