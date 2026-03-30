<?php
declare(strict_types=1);

namespace App\Controllers;

use Exception;
use Throwable;

/**
 * SCAFFOLD CONTROLLER - Full Schema Reset
 * Orchestrates the creation of the normalized Neural Ingestion database.
 */
class ScaffoldController extends BaseController
{
    /**
     * Primary entry point for database initialization.
     */
    public function migrate(): void
    {
        try {
            if (!$this->db) {
                throw new Exception("Database Service failed to initialize.");
            }

            $applied = [];
            
            // 1. Core Table Synchronization
            $definitions = $this->getTableDefinitions();
            foreach ($definitions as $name => $schema) {
                $this->db->createTable($name, $schema);
                $applied[] = "Table verified: $name";
            }

            // 2. Incremental Schema Alterations (Empty for new baseline)
            $patches = $this->applyAlterations();
            $applied = array_merge($applied, $patches);

            $this->json([
                'status'    => 'success',
                'applied'   => $applied,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Throwable $e) {
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
     * (Reset to empty as all columns are now in the Master Blueprint)
     */
    private function applyAlterations(): array
    {
        return []; 
    }

    /**
     * The Master Blueprint (Normalized Schema)
     */
private function getTableDefinitions(): array
{
    return [
        'users' => [
            'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
            'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
            'password'   => 'VARCHAR(255) NOT NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'addresses' => [
            'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id'     => 'INT NOT NULL',
            'line_1'      => 'VARCHAR(255)',
            'city'        => 'VARCHAR(100)',
            'postcode'    => 'VARCHAR(20)',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'telephones' => [
            'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id'     => 'INT NOT NULL',
            'number'      => 'VARCHAR(50) NOT NULL',
            'type'        => 'VARCHAR(20) DEFAULT "mobile"',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'emails' => [
            'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id'     => 'INT NOT NULL',
            'address'     => 'VARCHAR(255) NOT NULL UNIQUE',
            'is_primary'  => 'TINYINT(1) DEFAULT 0',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'employers' => [
            'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id'     => 'INT NOT NULL',
            'company'     => 'VARCHAR(255) NOT NULL',
            'job_title'   => 'VARCHAR(255)',
            'start_date'  => 'DATE',
            'created_at'  => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'jobs' => [
            'id'           => 'INT AUTO_INCREMENT PRIMARY KEY',
            'title'        => 'VARCHAR(255) NOT NULL',
            'type'         => 'VARCHAR(50) DEFAULT "neural_ingest"',
            'payload'      => 'TEXT',
            'status'       => 'VARCHAR(50) DEFAULT "pending"',
            'current_step' => 'VARCHAR(255) DEFAULT NULL',
            'progress'     => 'INT DEFAULT 0',
            'finished_at'  => 'DATETIME DEFAULT NULL',
            'created_at'   => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'vectors' => [
            'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
            'job_id'     => 'INT NOT NULL',
            'content'    => 'TEXT NOT NULL',
            'embedding'  => 'JSON NOT NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'documents' => [
            'id'         => 'VARCHAR(36) PRIMARY KEY',
            'filename'   => 'VARCHAR(255) NOT NULL',
            'status'     => "ENUM('pending', 'processing', 'active', 'archived') DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        ],
        'knowledge_chunks' => [
            'id'              => 'VARCHAR(36) PRIMARY KEY',
            'document_id'     => 'VARCHAR(36)',
            'chunk_index'     => 'INT',
            'content_preview' => 'TEXT',
            'valid_from'      => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'valid_until'     => 'TIMESTAMP NULL DEFAULT NULL',
            'version_id'      => 'VARCHAR(50)',
            'FOREIGN KEY'     => '(document_id) REFERENCES documents(id) ON DELETE CASCADE',
            'INDEX'           => 'idx_temporal_validity (valid_from, valid_until)'
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