<?php
declare(strict_types=1);

namespace App\Controllers;

// CRITICAL FIX: Match the Registry namespace defined in bootstrap.php
use App\Core\Registry; 
use Exception;
use Throwable;

/**
 * SCAFFOLD CONTROLLER
 * Serves as the primary entry point for system maintenance and module blueprints.
 */
class ScaffoldController extends BaseController
{
    /**
     * Constructor: Initializes BaseController (Registry, DB, Loc)
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /php/scaffold
     * Health check for the Scaffold module.
     */
    public function index(): void
    {
        try {
            $data = [
                'module'    => 'Scaffold',
                'status'    => 'operational',
                'registry'  => 'connected',
                'timestamp' => time()
            ];

            $this->json($data);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'Failed to load Scaffold data.'
            ], 500);
        }
    }

    /**
     * GET /php/scaffold/migrate
     * Builds the essential tables for the project.
     */
    public function migrate(): void
    {
        try {
            if (!$this->db) {
                throw new Exception("Database connection not found in Registry.");
            }

            $tables = $this->getTableDefinitions();
            $applied = [];

            foreach ($tables as $name => $schema) {
                /**
                 * FIX: Based on PHP TypeError, createTable expects (string $table, array $columns)
                 * rather than a single wrapper array.
                 */
                $this->db->createTable($name, $schema);
                
                $applied[] = "Table '$name' is ready.";
            }

            $this->json([
                'status' => 'success',
                'applied' => $applied,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Define the blueprint for the application tables
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