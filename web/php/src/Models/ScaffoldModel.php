<?php

declare(strict_types=1);

namespace App\Models;

class ScaffoldModel extends BaseModel
{
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

    public function getTableDefinitions(): array
    {
        return [
            'migrations' => [
                'id'             => 'INT AUTO_INCREMENT PRIMARY KEY',
                'migration_name' => 'VARCHAR(255) UNIQUE NOT NULL',
                'executed_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'batch'          => 'INT DEFAULT 1',
                'pref'           => 'VARCHAR(255) DEFAULT NULL',
                'content'        => 'LONGTEXT NULL DEFAULT NULL',
                'status'         => 'VARCHAR(255)',
            ],

            'users' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'username'   => 'VARCHAR(100) NOT NULL UNIQUE',
                'password'   => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)',
            ],

            'documents' => [
                'id'         => 'VARCHAR(36) PRIMARY KEY',
                'filename'   => 'VARCHAR(255) NOT NULL',
                'status'     => "ENUM('pending', 'processing', 'active', 'archived') DEFAULT 'pending'",
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'jobs' => [
                'id'                => 'INT AUTO_INCREMENT PRIMARY KEY',
                'payload'           => 'LONGTEXT NULL DEFAULT NULL',
                'status'            => "ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'",
                'current_step'      => 'VARCHAR(255) DEFAULT NULL',
                'progress'          => 'INT DEFAULT 0',
                'created_at'        => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'finished_at'       => 'TIMESTAMP NULL DEFAULT NULL',
                'embedding_version' => 'VARCHAR(50) DEFAULT NULL',
                'processed_at'      => 'TIMESTAMP NULL DEFAULT NULL',
                'error_message'     => 'TEXT NULL',
                'pref'              => 'VARCHAR(255) DEFAULT NULL',
                'content'           => 'LONGTEXT NULL DEFAULT NULL',
            ],

            'vectors' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'job_id'     => 'INT NOT NULL',
                'embedding'  => 'JSON NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)',
            ],

            'knowledge_chunks' => [
                'id'              => 'VARCHAR(36) PRIMARY KEY',
                'document_id'     => 'VARCHAR(36) NOT NULL',
                'chunk_index'     => 'INT NOT NULL',
                'content_preview' => 'TEXT',
                'valid_from'      => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'valid_until'     => 'TIMESTAMP NULL DEFAULT NULL',
                'version_id'      => 'VARCHAR(50)',
                'pref'            => 'VARCHAR(255) DEFAULT NULL',
                'content'         => 'LONGTEXT NULL DEFAULT NULL',
                'status'          => 'VARCHAR(255)',
            ],

            'logs' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'level'      => 'VARCHAR(20)',
                'message'    => 'TEXT',
                'context'    => 'JSON',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)',
            ],

            'queries' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'      => 'VARCHAR(225)',
                'message'    => 'VARCHAR(225)',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)',
            ],

            'agent' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'      => 'VARCHAR(225)',
                'message'    => 'VARCHAR(225)',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)'
            ],

            'emails' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'      => 'VARCHAR(225)',
                'message'    => 'VARCHAR(225)',
                'email'      => 'VARCHAR(225)',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)'
            ],

            'agents' => [
                'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'       => 'VARCHAR(225)',
                'agent_name'  => 'VARCHAR(225)',
                'category'    => 'VARCHAR(100) DEFAULT "career"',
                'summary'     => 'VARCHAR(255) NULL',
                'description' => 'VARCHAR(225)',
                'role'        => 'VARCHAR(225)',
                'message'     => 'VARCHAR(225)',
                'created_at'  => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'        => 'VARCHAR(255) DEFAULT NULL',
                'content'     => 'LONGTEXT NULL DEFAULT NULL',
                'status'      => 'VARCHAR(225)',
            ],

            'snapshots' => [
                'id'          => 'INT AUTO_INCREMENT PRIMARY KEY',
                'title'       => 'VARCHAR(225)',
                'agent_name'  => 'VARCHAR(225)',
                'description' => 'VARCHAR(225)',
                'role'        => 'VARCHAR(225)',
                'message'     => 'VARCHAR(225)',
                'created_at'  => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'        => 'VARCHAR(255) DEFAULT NULL',
                'content'     => 'LONGTEXT NULL DEFAULT NULL',
                'status'      => 'VARCHAR(225)'
            ],

            'snapshot' => [
                'id'           => 'INT AUTO_INCREMENT PRIMARY KEY',
                'snapshots_id' => 'VARCHAR(225)',
                'title'        => 'VARCHAR(225)',
                'description'  => 'VARCHAR(225)',
                'page'         => 'VARCHAR(225)',
                'message'      => 'VARCHAR(225)',
                'created_at'   => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'         => 'VARCHAR(255) DEFAULT NULL',
                'content'      => 'LONGTEXT NULL DEFAULT NULL',
                'status'       => 'VARCHAR(255)'
            ],

            'google_tokens' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id'       => 'INT NOT NULL',
                'provider'      => 'VARCHAR(50) NOT NULL',
                'access_token'  => 'TEXT NOT NULL',
                'refresh_token' => 'TEXT',
                'scopes'        => 'TEXT',
                'expires_at'    => 'TIMESTAMP NULL',
                'revoked_at'    => 'TIMESTAMP NULL',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],

            'oauth_tokens' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id'       => 'INT NOT NULL',
                'provider'      => 'VARCHAR(50) NOT NULL',
                'access_token'  => 'TEXT NOT NULL',
                'refresh_token' => 'TEXT NULL',
                'scopes'        => 'TEXT NULL',
                'expires_at'    => 'TIMESTAMP NULL DEFAULT NULL',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],

            'hotmail_tokens' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id'       => 'INT NOT NULL',
                'access_token'  => 'TEXT NOT NULL',
                'refresh_token' => 'TEXT NULL',
                'expires_at'    => 'TIMESTAMP NULL DEFAULT NULL',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],

            'aws_tokens' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id'       => 'INT NOT NULL',
                'access_key'    => 'VARCHAR(255) NOT NULL',
                'secret_key'    => 'TEXT NOT NULL',
                'region'        => 'VARCHAR(50) DEFAULT "eu-west-1"',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],

            'indeed_tokens' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'user_id'       => 'INT NOT NULL',
                'access_token'  => 'TEXT NOT NULL',
                'expires_at'    => 'TIMESTAMP NULL DEFAULT NULL',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],

            'pentest_scans' => [
                'id'            => 'INT AUTO_INCREMENT PRIMARY KEY',
                'target'        => 'VARCHAR(255) NOT NULL',
                'scan_type'     => 'VARCHAR(100) DEFAULT "diagnostics"',
                'results'       => 'LONGTEXT NULL DEFAULT NULL',
                'status'        => 'VARCHAR(50) DEFAULT "pending"',
                'created_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            ],

            'terminal' => [
                'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
                'filename'   => 'VARCHAR(225)',
                'command'    => 'VARCHAR(225)',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'pref'       => 'VARCHAR(255) DEFAULT NULL',
                'content'    => 'LONGTEXT NULL DEFAULT NULL',
                'status'     => 'VARCHAR(255)'
            ],
        ];
    }
}