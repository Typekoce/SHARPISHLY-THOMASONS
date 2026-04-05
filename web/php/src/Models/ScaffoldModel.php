<?php

declare(strict_types=1);

namespace App\Models;

/**
 * SCAFFOLD MODEL
 * The absolute source of truth for the Database Schema.
 * Centralizes the Master Blueprint for the Sharpishly ecosystem.
 */
class ScaffoldModel extends BaseModel
{
    /**
     * Executes the creation/verification of all tables in the blueprint.
     * @return array List of verified tables.
     */
    public function syncSchema(): array
    {
        $applied = [];
        $definitions = $this->getTableDefinitions();

        foreach ($definitions as $name => $schema) {
            // Internal DB service handles the 'CREATE TABLE IF NOT EXISTS' logic
            $this->db->createTable($name, $schema);
            $applied[] = "Table verified: $name";
        }

        return $applied;
    }

    /**
     * The Master Blueprint (Normalized Schema)
     * Centralized here so all modules validate against the same structure.
     */
    public function getTableDefinitions(): array
    {
        return [
            'migrations' => [
                'id'             => 'INT AUTO_INCREMENT PRIMARY KEY',
                'migration_name' => 'VARCHAR(255) UNIQUE NOT NULL',
                'executed_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'batch'          => 'INT DEFAULT 1'
            ],
            'users' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
                'password'   => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ],
            'jobs' => [
                'id'           => 'INT AUTO_INCREMENT PRIMARY KEY',
                'payload'      => 'JSON NOT NULL',
                'status'       => "ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'",
                'current_step' => 'VARCHAR(255) DEFAULT NULL',
                'progress'     => 'INT DEFAULT 0',
                'created_at'   => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'finished_at'  => 'TIMESTAMP NULL DEFAULT NULL',
                'INDEX idx_status' => '(status)'
            ],
            'vectors' => [
                'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
                'job_id'      => 'INT NOT NULL',
                'content'     => 'TEXT NOT NULL',
                'embedding'   => 'JSON NOT NULL',
                'created_at'  => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'FOREIGN KEY' => '(job_id) REFERENCES jobs(id) ON DELETE CASCADE',
                'INDEX idx_job_id' => '(job_id)'
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
                'INDEX idx_temporal' => '(valid_from, valid_until)'
            ],
            'logs' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'level'      => 'VARCHAR(20)',
                'message'    => 'TEXT',
                'context'    => 'JSON',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ]
        ];
    }
}