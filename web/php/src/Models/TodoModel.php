<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Orm;
use Throwable;

class TodoModel extends BaseModel
{
    protected Orm $orm;

    public function __construct()
    {
        parent::__construct();
        $this->orm = new Orm();
    }

    /**
     * Insert task using $conditions via Orm service
     */
    public function createTask(array $taskData): array
    {
        $conditions = [
            'action' => 'insert',
            'table'  => 'todos',
            'data'   => [
                'title'       => $taskData['title'] ?? '',
                'category'    => $taskData['category'] ?? '',
                'description' => $taskData['description'] ?? '',
                'status'      => $taskData['status'] ?? 'pending',
                'created_at'  => $taskData['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'  => $taskData['updated_at'] ?? date('Y-m-d H:i:s'),
            ],
        ];

        try {
            $response = $this->orm->execute($conditions);
            $newId = (int)($response['insert_id'] ?? 0);

            return array_merge(['id' => $newId], $taskData);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve single task or list of tasks with optional filtering via Orm service
     */
    public function getTasks(string $id = '', array $filters = []): array
    {
        $where = [];

        if (!empty($id)) {
            $where['id'] = $id;
        }

        if (!empty($filters['status'])) {
            $where['status'] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where['category'] = $filters['category'];
        }

        $conditions = [
            'action' => 'select',
            'table'  => 'todos',
            'where'  => $where,
            'order'  => 'id DESC',
        ];

        if (!empty($id)) {
            $conditions['limit'] = 1;
        }

        try {
            $response = $this->orm->execute($conditions);
            if (!empty($id)) {
                return $response[0] ?? [];
            }
            return is_array($response) ? $response : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Update task status or details by ID using Orm service
     */
    public function updateTask(string $id, array $updateData): array
    {
        if (empty($updateData)) {
            return ['id' => $id, 'updated' => false];
        }

        $conditions = [
            'action' => 'update',
            'table'  => 'todos',
            'data'   => $updateData,
            'where'  => [
                'id' => $id,
            ],
        ];

        try {
            $response = $this->orm->execute($conditions);
            return [
                'id'      => $id,
                'updated' => !empty($response['success']),
                'fields'  => array_keys($updateData),
            ];
        } catch (Throwable $e) {
            return ['id' => $id, 'updated' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete task record by ID via Orm service
     */
    public function deleteTask(string $id): array
    {
        $conditions = [
            'action' => 'delete',
            'table'  => 'todos',
            'where'  => [
                'id' => $id,
            ],
        ];

        try {
            $response = $this->orm->execute($conditions);
            return [
                'id'      => $id,
                'deleted' => !empty($response['success']),
            ];
        } catch (Throwable $e) {
            return ['id' => $id, 'deleted' => false, 'error' => $e->getMessage()];
        }
    }
}