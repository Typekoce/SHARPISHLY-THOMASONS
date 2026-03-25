<?php
declare(strict_types=1);

namespace App\Controllers;

// CRITICAL FIX: Match the Registry namespace defined in bootstrap.php
use App\Core\Registry; 
use App\Services\Migrator;
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
     * Executes the SQL migration sequence (001-005).
     */
    public function migrate(): void
    {
        try {
            // Migrator is initialized; it will pull the DB from the Registry internally
            $migrator = new Migrator();
            $results  = $migrator->run();
            
            $this->json([
                'status'    => 'success',
                'applied'   => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            // Note: debug_backtrace is useful for Dev but should be hidden in Prod
            $this->json([
                'status'  => 'error',
                'message' => "Migration Failed: " . $e->getMessage(),
                'trace'   => (getenv('APP_ENV') === 'development') ? $e->getTraceAsString() : 'hidden'
            ], 500);
        }
    }
}