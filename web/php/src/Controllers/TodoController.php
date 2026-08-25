<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TodoModel;
use Throwable;

class TodoController extends BaseController
{
    public TodoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new TodoModel();
    }

    /**
     * Display status of all tasks or a specific task by ID
     */
    public function index(string $id = ''): void
    {
        $this->logger->info("TodoController::index called with ID: '{$id}'");
        $this->read($id);
    }

    /**
     * Create a new task tracking record
     */
    public function create(): void
    {
        $payload = $this->request();
        $this->logger->info("TodoController::create initiated", ['payload' => $payload]);
        
        $title       = trim((string)($payload['title'] ?? ''));
        $category    = trim((string)($payload['category'] ?? 'internal'));
        $description = trim((string)($payload['description'] ?? ''));
        $status      = trim((string)($payload['status'] ?? 'pending'));

        if (empty($title)) {
            $this->logger->warning("TodoController::create failed: Title is required");
            $this->json(['status' => 'error', 'message' => 'Task title is required.'], 400);
            return;
        }

        try {
            $result = $this->model->createTask([
                'title'       => $title,
                'category'    => $category,
                'description' => $description,
                'status'      => $status,
                'created_at'  => $this->timestamp(),
                'updated_at'  => $this->timestamp(),
            ]);

            $this->logger->info("TodoController::create task created successfully");
            $this->json(['status' => 'success', 'data' => $result]);
        } catch (Throwable $e) {
            $this->logger->error("TodoController::create Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Read task status (all tasks or single record with optional status/category filters)
     */
    public function read(string $id = ''): void
    {
        $this->logger->info("TodoController::read requested for ID: '{$id}'");

        try {
            $status   = $this->request('status');
            $category = $this->request('category');

            $filters = [];
            if ($status !== null && trim((string)$status) !== '') {
                $filters['status'] = trim((string)$status);
            }
            if ($category !== null && trim((string)$category) !== '') {
                $filters['category'] = trim((string)$category);
            }

            $data = $this->model->getTasks($id, $filters);
            $this->logger->info("TodoController::read completed successfully");
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Throwable $e) {
            $this->logger->error("TodoController::read Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update task details (title, status, description, category)
     */
    public function update(string $id = ''): void
    {
        $taskId = $id !== '' ? $id : (string)($this->request('id') ?? '');
        $this->logger->info("TodoController::update initiated for Task ID: '{$taskId}'");

        $status = $this->request('status');
        $title  = $this->request('title');
        $desc   = $this->request('description');
        $cat    = $this->request('category');

        if (empty($taskId)) {
            $this->logger->warning("TodoController::update failed: Missing task ID");
            $this->json(['status' => 'error', 'message' => 'Task ID is required for update.'], 400);
            return;
        }

        try {
            $updateData = [
                'updated_at' => $this->timestamp(),
            ];

            if ($status !== null) {
                $updateData['status'] = trim((string)$status);
            }
            if ($title !== null) {
                $updateData['title'] = trim((string)$title);
            }
            if ($desc !== null) {
                $updateData['description'] = trim((string)$desc);
            }
            if ($cat !== null) {
                $updateData['category'] = trim((string)$cat);
            }

            $result = $this->model->updateTask($taskId, $updateData);
            $this->logger->info("TodoController::update completed for Task ID: '{$taskId}'");
            $this->json(['status' => 'success', 'data' => $result]);
        } catch (Throwable $e) {
            $this->logger->error("TodoController::update Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete/Archive a task record
     */
    public function delete(string $id = ''): void
    {
        $taskId = $id !== '' ? $id : (string)($this->request('id') ?? '');
        $this->logger->info("TodoController::delete requested for Task ID: '{$taskId}'");

        if (empty($taskId)) {
            $this->logger->warning("TodoController::delete failed: Missing task ID");
            $this->json(['status' => 'error', 'message' => 'Task ID is required for deletion.'], 400);
            return;
        }

        try {
            $result = $this->model->deleteTask($taskId);
            $this->logger->info("TodoController::delete completed for Task ID: '{$taskId}'");
            $this->json(['status' => 'success', 'data' => $result]);
        } catch (Throwable $e) {
            $this->logger->error("TodoController::delete Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}