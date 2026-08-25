<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class TodoModel extends BaseModel
{
    /**
     * Create a new task record
     */
    public function createTask(array $taskData): array
    {
        try {
            $response = $this->db->save([
                'tbl'  => 'todos',
                'save' => [
                    'title'       => $taskData['title'] ?? '',
                    'category'    => $taskData['category'] ?? 'internal',
                    'description' => $taskData['description'] ?? '',
                    'status'      => $taskData['status'] ?? 'pending',
                    'created_at'  => $taskData['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at'  => $taskData['updated_at'] ?? date('Y-m-d H:i:s'),
                ],
            ]);

            return array_merge(['id' => (int)($response['insert_id'] ?? 0)], $taskData);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch tasks by ID or criteria
     */
    public function getTasks(string $id = '', array $filters = []): array
    {
        $where = [];

        if ($id !== '') {
            $where['id'] = $id;
        }
        if (!empty($filters['status'])) {
            $where['status'] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $where['category'] = $filters['category'];
        }

        try {
            $conditions = [
                'tbl'   => 'todos',
                'where' => $where,
            ];

            $response = $this->db->find($conditions);

            if ($id !== '') {
                return $response[0] ?? [];
            }

            return is_array($response) ? $response : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Update an existing task
     */
    public function updateTask(string $id, array $updateData): array
    {
        if (empty($updateData)) {
            return ['id' => $id, 'updated' => false];
        }

        try {
            $response = $this->db->save([
                'tbl'   => 'todos',
                'save'  => $updateData,
                'where' => ['id' => $id],
            ]);

            return [
                'id'      => $id,
                'updated' => !empty($response),
            ];
        } catch (Throwable $e) {
            return ['id' => $id, 'updated' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a task by ID
     */
    public function deleteTask(string $id): array
    {
        try {
            $response = $this->db->delete([
                'tbl'   => 'todos',
                'where' => ['id' => $id],
            ]);

            return [
                'id'      => $id,
                'deleted' => !empty($response),
            ];
        } catch (Throwable $e) {
            return ['id' => $id, 'deleted' => false, 'error' => $e->getMessage()];
        }
    }
}