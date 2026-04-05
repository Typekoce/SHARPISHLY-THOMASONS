<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use src\Services\Session;

/**
 * HOME CONTROLLER
 * Handles core module routes, administrative views, and schema orchestration.
 */
class HomeController extends BaseController
{
    protected Session $session;

    public function __construct()
    {
        parent::__construct();
        $this->session = Session::getInstance();
    }

    /**
     * GET /php/home
     * Default entry point for the module.
     */
    public function index(): void
    {
        try {
            $data = [
                'module'    => 'Home',
                'status'    => 'operational',
                'action'    => 'index',
                'timestamp' => time()
            ];

            $this->json($data);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'Failed to load Home data.'
            ], 500);
        }
    }

    /**
     * GET /php/home/migrate
     * Handles database migration / scaffolding handshake.
     * Accessible via PyMVC or direct HTTP request.
     */
    public function migrate(): void
    {
        // 1. Session Gate - Fastest check
        if ($this->session->get('migrated', false) === true) {
            $this->json([
                'status'  => 'skip',
                'message' => 'Migration already verified for this session.'
            ]);
            return;
        }

        $lastMigration = 'v3_6_neural_pipeline_init';

        try {
            // 2. Physical Migration Check (Truth Source)
            $alreadyRun = $this->db->find([
                'tbl'   => 'migrations',
                'where' => ['migration_name' => $lastMigration]
            ]);

            if (empty($alreadyRun)) {
                // 3. Execution Phase
                $this->runMigration();

                // Record the execution
                $this->db->save('migrations', [
                    'migration_name' => $lastMigration,
                    'batch'          => 1
                ]);

                $this->session->set('migrated', true);
                $this->json(['status' => 'success', 'message' => 'Schema migration completed.']);
            } else {
                // Sync session if DB is already ahead
                $this->session->set('migrated', true);
                $this->json(['status' => 'skip', 'message' => 'Migration already applied in DB.']);
            }

        } catch (Exception $e) {
            $this->session->set('migrated', false); 
            $this->json(['status' => 'error', 'message' => 'Migration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /php/home/jobs
     * Fetches all records from the jobs table.
     */
    public function jobs(): void
    {
        $conditions = [
            'tbl'   => 'jobs',
            'order' => ['id' => 'DESC']
        ];

        $rs = $this->db->find($conditions);
        $this->json($rs);
    }

    /**
     * POST /php/home/action
     */
    public function action(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['status' => 'error', 'message' => 'Invalid payload.'], 400);
            return;
        }

        try {
            $this->json([
                'status'  => 'success',
                'message' => 'Action completed successfully.'
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Private Schema Definition logic
     */
    private function runMigration(): void
    {
        // Create the tracking table first
        $this->db->createTable('migrations', [
            'id'             => 'INT AUTO_INCREMENT PRIMARY KEY',
            'migration_name' => 'VARCHAR(255) UNIQUE NOT NULL',
            'executed_at'    => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'batch'          => 'INT DEFAULT 1'
        ]);

        // Create core pipeline tables
        $this->db->createTable('jobs', [
            'id'           => 'INT AUTO_INCREMENT PRIMARY KEY',
            'payload'      => 'JSON NOT NULL',
            'status'       => "ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending'",
            'progress'     => 'INT DEFAULT 0',
            'created_at'   => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'finished_at'  => 'TIMESTAMP NULL DEFAULT NULL',
            'INDEX idx_status (status)'
        ]);

        $this->db->createTable('vectors', [
            'id'         => 'INT AUTO_INCREMENT PRIMARY KEY',
            'job_id'     => 'INT NOT NULL',
            'content'    => 'TEXT NOT NULL',
            'embedding'  => 'JSON NOT NULL',
            'FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE',
            'INDEX idx_job_id (job_id)'
        ]);
    }
}