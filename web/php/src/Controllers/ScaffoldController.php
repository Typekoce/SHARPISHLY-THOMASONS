<?php
declare(strict_types=1);

namespace App\Controllers;
// Location: php/src/Controllers/ScaffoldController.php
use App\Registry;
use Exception;
use App\Services\Migrator;

/**
 * SCAFFOLD CONTROLLER
 * Use this as a blueprint for new modules (e.g., Email, Calendar, Social).
 */
class ScaffoldController extends BaseController
{
    /**
     * @var mixed The primary model for this controller
     */
    private $model;

    public function __construct()
    {
        // Initialize specific models or services via Registry here
        // $this->model = new \App\Models\ExampleModel();
    }

    /**
     * GET /php/scaffold
     * Default entry point for the module.
     */
    public function index(): void
    {
        try {
            // Business logic goes here
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
     * POST /php/scaffold/action
     * Example of a write/update action.
     */
    public function action(): void
    {
        // 1. Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['status' => 'error', 'message' => 'Invalid payload.'], 400);
            return;
        }

        try {
            // 2. Process via Model (Using App\Db internally)
            // $result = $this->model->saveSomething($input);

            $this->json([
                'status' => 'success',
                'message' => 'Action completed successfully.'
            ]);
        } catch (Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function migrate(): void
    {
        // Enforce JSON header for the dev-up script
        header('Content-Type: application/json');

        try {
            $migrator = new Migrator();
            $results = $migrator->run();
            
            echo json_encode([
                'status' => 'success',
                'applied' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
