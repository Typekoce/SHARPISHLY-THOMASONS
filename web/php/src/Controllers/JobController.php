<?php
declare(strict_types=1);

namespace App\Controllers;
// Location: php/src/Controllers/JobController.php
use App\Registry;
use Exception;

/**
 * Job CONTROLLER
 * Use this as a blueprint for new modules (e.g., Email, Calendar, Social).
 */
class JobController extends BaseController
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
     * GET /php/Job
     * Default entry point for the module.
     */
    public function index(): void
    {
        try {
            // Business logic goes here
            $data = [
                'module' => 'Job',
                'status' => 'operational',
                'timestamp' => time()
            ];

            $this->json($data);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Failed to load Job data.'
            ], 500);
        }
    }

    public function getStatus(int $jobId): never
    {
        $db = Registry::get(Db::class);

        //@TODO: Use $conditions
        $stmt = $db->prepare("
            SELECT status, current_step, steps_json, error_log 
            FROM jobs 
            WHERE id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status'     => $job['status'],
            'current'    => $job['current_step'] ?? 'Initializing...',
            'history'    => json_decode($job['steps_json'] ?? '[]', true),
            'error'      => $job['error_log'] ? substr($job['error_log'], 0, 300) . '...' : null,
        ], JSON_THROW_ON_ERROR);
        exit;
    }

}