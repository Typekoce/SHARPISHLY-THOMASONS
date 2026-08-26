<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class TodoModel extends BaseModel
{
    public bool $debug = true;

    public function create(array $payload = []): array
    {
        if ($this->debug === true && empty($payload)) {
            $payload = [
                'title'       => 'AWS Queue',
                'description' => 'Complete proposal to prospective companies',
                'source_id'   => 1,
                'status'      => 'active'
            ];
        }

        $this->db->save('todo', $payload);
        return ['result' => $this->read()];
    }

    public function update(string $id = '', array $payload = []): array
    {
        if ($this->debug === true && empty($payload)) {
            $payload = [
                'title'       => 'AWS Queue - Updated',
                'description' => 'Complete proposal to prospective companies',
                'source_id'   => 1,
                'status'      => 'completed'
            ];
        }

        if (!empty($id) && !empty($payload)) {
            // Include ID in payload for Db::save to perform an UPDATE instead of INSERT
            $payload['id'] = $id;
            $this->db->save('todo', $payload);
        }

        return ['result' => $this->read($id)];
    }

    public function read(string $id = ''): array
    {
        try {
            $conditions = [
                'tbl'   => 'todo',
                'order' => ['id' => 'DESC']
            ];

            if (!empty($id)) {
                $conditions['where'] = ['id' => $id];
            }

            return $this->db->find($conditions);
        } catch (Throwable $e) {
            $logger = $GLOBALS['logger'] ?? null;
            if ($logger) {
                $logger->error("TodoModel::read failed: " . $e->getMessage());
            }

            return [
                'error'   => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete(string $id = ''): array
    {
        if (empty($id)) {
            return ['error' => true, 'message' => 'Missing ID for deletion'];
        }

        try {
            $this->db->delete('todo', ['id' => $id]);
            return ['status' => 'deleted', 'id' => $id];
        } catch (Throwable $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}