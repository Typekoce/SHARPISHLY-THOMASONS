<?php
declare(strict_types=1);

namespace App\Services;

use App\Registry;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class Migrator
{
    private PDO $db;
    private string $migrationsDir;

    public function __construct()
    {
        $this->db = Registry::get(Db::class)->getConnection(); // assuming Db::getConnection() returns PDO
        $this->migrationsDir = rtrim(__DIR__ . '/../../database/migrations', '/') . '/';

        if (!is_dir($this->migrationsDir) || !is_readable($this->migrationsDir)) {
            throw new RuntimeException("Migrations directory not found or not readable: {$this->migrationsDir}");
        }
    }

    /**
     * Run all pending migrations in order.
     * Returns array of status messages.
     */
    public function run(): array
    {
        $results = [];
        $this->ensureMigrationsTable();

        $executed = $this->getExecutedMigrations();
        $files = $this->getPendingMigrationFiles($executed);

        if (empty($files)) {
            return ['System is up to date. No new migrations found.'];
        }

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            try {
                $sql = file_get_contents($filePath);
                if ($sql === false) {
                    throw new RuntimeException("Failed to read migration file");
                }

                $this->db->exec($sql);

                $this->recordMigration($filename);

                $results[] = "✅ Applied: $filename";
            } catch (PDOException $e) {
                $msg = "❌ Failed: $filename - Database error: " . $e->getMessage();
                $results[] = $msg;
                error_log($msg); // still log to system for debugging
                break; // Stop on failure – do not continue
            } catch (Throwable $e) {
                $msg = "❌ Failed: $filename - " . $e->getMessage();
                $results[] = $msg;
                error_log($msg);
                break;
            }
        }

        return $results;
    }

    /**
     * Create the tracking table if it doesn't exist
     */
    private function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                migration     VARCHAR(255) NOT NULL UNIQUE,
                executed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Get list of already executed migration filenames
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * Find .sql files that haven't been executed yet
     */
    private function getPendingMigrationFiles(array $executed): array
    {
        $allFiles = glob($this->migrationsDir . '*.sql');

        if ($allFiles === false) {
            return [];
        }

        sort($allFiles); // lexical order → assumes 001_xxx.sql, 002_yyy.sql naming

        return array_filter($allFiles, function ($file) use ($executed) {
            return !in_array(basename($file), $executed, true);
        });
    }

    /**
     * Record that a migration was successfully applied
     */
    private function recordMigration(string $filename): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO migrations (migration)
            VALUES (:migration)
            ON DUPLICATE KEY UPDATE executed_at = NOW()
        ");
        $stmt->execute(['migration' => $filename]);
    }
}