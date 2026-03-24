<?php
declare(strict_types=1);

namespace App\Controllers;

// FIXED: Point to the actual location of the Registry
use App\Core\Registry; 
use Exception;
use App\Services\Migrator;
use Throwable;

/**
 * SCAFFOLD CONTROLLER
 * Use this as a blueprint for new modules (e.g., Email, Calendar, Social).
 */
class ScaffoldController extends BaseController
{
    private $model;

    public function __construct()
    {
        // IMPORTANT: Call the parent constructor to initialize $this->db, $this->loc, etc.
        parent::__construct();
    }

    /**
     * GET /php/scaffold
     */
    public function index(): void
    {
        try {
            $data = [
                'module' => 'Scaffold',
                'status' => 'operational',
                'timestamp' => time()
            ];

            $this->json($data);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Failed to load Scaffold data.'
            ], 500);
        }
    }

    /**
     * GET /php/scaffold/migrate
     */
    public function migrate(): void
    {
        try {
            // Migrator needs to be namespaced App\Services\Migrator
            $migrator = new Migrator();
            $results = $migrator->run();
            
            $this->json([
                'status' => 'success',
                'applied' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => "Migration Failed: " . $e->getMessage(),
                'debud-back-trace' => debug_backtrace()
            ], 500);
        }
    }
}