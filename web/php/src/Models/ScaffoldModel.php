<?php
declare(strict_types=1);

namespace App\Models;

/**
 * SCAFFOLD MODEL
 * Owns the Schema Blueprint and execution logic.
 */
class ScaffoldModel extends BaseModel
{
    /**
     * Executes the creation of all tables in the blueprint.
     */
    public function syncSchema(): array
    {
        $applied = [];
        $definitions = $this->getTableDefinitions();

        foreach ($definitions as $name => $schema) {
            // We call the createTable method on our DB service
            $this->db->createTable($name, $schema);
            $applied[] = "Table verified: $name";
        }

        return $applied;
    }

    /**
     * The Master Blueprint (Normalized Schema)
     * Centralized here so Models can eventually validate themselves against it.
     */
    public function getTableDefinitions(): array
    {
        return [
            'users' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
                'password'   => 'VARCHAR(255) NOT NULL',
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
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
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'INDEX'      => 'idx_job_id (job_id)'
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