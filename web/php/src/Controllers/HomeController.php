<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use src\Services\Session;

/**
 * HOME CONTROLLER
 * Handles core module routes and administrative views like job logs.
 * Migration logic has been moved to ScaffoldController.
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
     * GET /php/home/jobs
     * Fetches all records from the jobs table for the administration dashboard.
     */
    public function jobs(): void
    {
        try {
            $conditions = [
                'tbl'   => 'jobs',
                'order' => ['id' => 'DESC']
            ];

            $rs = $this->db->find($conditions);
            $this->json($rs);
        } catch (Exception $e) {
            $this->json([
                'status'  => 'error',
                'message' => 'Unable to fetch jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /php/home/action
     * Generic entry point for module-specific write operations.
     */
    public function action(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['status' => 'error', 'message' => 'Invalid payload.'], 400);
            return;
        }

        try {
            // Execution logic for generic actions
            $this->json([
                'status'  => 'success',
                'message' => 'Action completed successfully.'
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}