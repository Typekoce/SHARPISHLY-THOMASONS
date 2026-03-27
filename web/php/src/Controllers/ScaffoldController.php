<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use Throwable;

/**
 * SCAFFOLD CONTROLLER
 * Orchestrates database migrations and structural integrity checks.
 */
class ScaffoldController extends BaseController
{
    /**
     * GET /php/scaffold/migrate
     * The primary entry point for the migrate.sh script.
     */
    public function migrate(): void
    {
        try {
            // $this->db is automatically populated by BaseController::__construct()
            if (!$this->db) {
                throw new Exception("Database Service (Registry['db']) failed to initialize.");
            }

            $applied = [];
            $definitions = $this->getTableDefinitions();

            // 1. Core Table Synchronization
            foreach ($definitions as $name => $schema) {
                $this->db->createTable($name, $schema);
                $applied[] = "Base table verified: $name";
            }

            // 2. Incremental Schema Alterations (The Delta Patches)
            $patches = $this->applyAlterations();
            $applied = array_merge($applied, $patches);

            $this->json([
                'status'    => 'success',
                'applied'   => $applied,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Throwable $e) {
            // Catching Throwable handles both Errors and Exceptions
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine()
            ], 500);
        }
    }

    /**
     * Managed Delta Migrations
     * Ensures new columns exist without breaking existing data.
     */
    private function applyAlterations(): array
    {
        $log = [];
        
        /**
         * UNIQUE PATCHES ONLY
         * Do not repeat columns across versions. 
         * v1_0_1 handles progress tracking.
         * v1_0_2 handles the completion timestamp.
         */
        $alterations = [
            'v1_0_1' => [
                'table' => 'jobs',
                'add'   => [
                    'current_step' => 'VARCHAR(255) DEFAULT NULL',
                    'progress'     => 'INT DEFAULT 0'
                ]
            ],
            'v1_0_2' => [
                'table' => 'jobs',
                'add'   => [
                    'finished_at'  => 'DATETIME DEFAULT NULL'
                ]
            ]
        ];

        foreach ($alterations as $version => $patch) {
            $table = $patch['table'];
            foreach ($patch['add'] as $column => $definition) {
                // This check will skip current_step/progress because they already exist
                if (!$this->db->columnExists($table, $column)) {
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
                    $this->db->execute($sql);
                    $log[] = "Patch $version: Added '$column' to '$table'.";
                }
            }
        }

        return $log;
    }

    /**
     * The Master Blueprint
     */
    private function getTableDefinitions(): array
    {
        return [
            'jobs' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'      => 'VARCHAR(255) NOT NULL',
                'payload'    => 'TEXT',
                'status'     => 'VARCHAR(50) DEFAULT "pending"',
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            ],
            'users' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
                'email'      => 'VARCHAR(255) NOT NULL UNIQUE',
                'password'   => 'VARCHAR(255) NOT NULL',
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            ],
            'logs' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'level'      => 'VARCHAR(20)',
                'message'    => 'TEXT',
                'context'    => 'JSON',
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
            ]
        ];
    }
}