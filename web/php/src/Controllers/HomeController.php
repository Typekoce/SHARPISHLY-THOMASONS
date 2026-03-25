<?php
declare(strict_types=1);

namespace App\Controllers;

// Location: php/src/Controllers/HomeController.php
use App\Core\Registry;
use Exception;

/**
 * HOME CONTROLLER
 * Handles core module routes and administrative views like job logs.
 */
class HomeController extends BaseController
{
    /**
     * Constructor: Initializes BaseController (Registry, DB, Loc)
     */
    public function __construct()
    {
        parent::__construct();
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
     * Fetches all records from the jobs table.
     */
    public function jobs(): void
    {
        $conditions = [
            'tbl'   => 'jobs',
            'order' => ['id' => 'DESC']
        ];

        // Using the Db wrapper's find method (No raw SQL)
        $rs = $this->db->find($conditions);

        // If the table is empty, this returns an empty array []
        $this->json($rs);
    }

    /**
     * POST /php/home/action
     * Example of a write/update action.
     */
    public function action(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['status' => 'error', 'message' => 'Invalid payload.'], 400);
            return;
        }

        try {
            // Example of a write operation via the DB abstraction
            // $this->db->insert(['tbl' => 'logs', 'data' => $input]);

            $this->json([
                'status'  => 'success',
                'message' => 'Action completed successfully.'
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}