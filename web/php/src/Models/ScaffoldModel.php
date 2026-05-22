<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ScaffoldModel - The Single Source of Truth for the Sharpishly Database Schema
 * Optimized for initial development by removing complex constraints.
 */
class ScaffoldModel extends BaseModel
{
    /**
     * Synchronizes all tables defined in the blueprint.
     * @return array List of tables that were verified/created
     */
    public function syncSchema(): array
    {
        $applied = [];
        $definitions = $this->getTableDefinitions();

        foreach ($definitions as $tableName => $schema) {
            $success = $this->db->createTable($tableName, $schema);
            $applied[] = $success 
                ? "✓ Table verified/created: $tableName" 
                : "✗ Failed to create table: $tableName";
        }

        return $applied;
    }

    /**
     * Master Blueprint - Bare Essentials
     * Removed INDEX and CONSTRAINT declarations to ensure a clean cold-start.
     */
    public function getTableDefinitions(): array
    {
        return [
            'migrations' => [
                'id'             => 'INT AUTO_INCREMENT PRIMARY KEY',
                'migration_name' => 'VARCHAR(255) UNIQUE NOT NULL',
                'executed_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'batch'          => 'INT DEFAULT 1',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'users' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
                'password'   => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'documents' => [
                'id'         => 'VARCHAR(36) PRIMARY KEY',
                'filename'   => 'VARCHAR(255) NOT NULL',
                'status'     => "ENUM('pending', 'processing', 'active', 'archived') DEFAULT 'pending'",
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'jobs' => [
                'id'           => 'INT AUTO_INCREMENT PRIMARY KEY',
                'payload' => 'LONGTEXT NULL DEFAULT NULL',
                'status'       => "ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'",
                'current_step' => 'VARCHAR(255) DEFAULT NULL',
                'progress'     => 'INT DEFAULT 0',
                'created_at'   => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'finished_at'  => 'TIMESTAMP NULL DEFAULT NULL',
                'embedding_version' => 'VARCHAR(50) DEFAULT NULL',
                'processed_at'      => 'TIMESTAMP NULL DEFAULT NULL',
                'error_message'     => 'TEXT NULL',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
                //'finished_at'       => 'DATETIME NULL'
            ],

            'vectors' => [
                'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
                'job_id'      => 'INT NOT NULL',
                //'content'     => 'TEXT NOT NULL',
                'embedding'   => 'JSON NOT NULL',
                'created_at'  => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'knowledge_chunks' => [
                'id'              => 'VARCHAR(36) PRIMARY KEY',
                'document_id'     => 'VARCHAR(36) NOT NULL',
                'chunk_index'     => 'INT NOT NULL',
                'content_preview' => 'TEXT',
                'valid_from'      => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'valid_until'     => 'TIMESTAMP NULL DEFAULT NULL',
                'version_id'      => 'VARCHAR(50)',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'logs' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'level'      => 'VARCHAR(20)',
                'message'    => 'TEXT',
                'context'    => 'JSON',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ],

	   'queries' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'      => 'VARCHAR(225)',
                'message'    => 'VARCHAR(225)',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'  => 'VARCHAR(255) DEFAULT NULL',
                'content' => 'LONGTEXT NULL DEFAULT NULL',
            ]
        ];
    }
}
