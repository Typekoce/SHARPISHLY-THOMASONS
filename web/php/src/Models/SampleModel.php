<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class SampelModel extends BaseModel
{
    public bool $debug = true;

    public $tbl = 'sample'

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

        $this->db->save($this->tbl, $payload);
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

        if (!empty($id)) {
            // Fetch existing record to preserve fields omitted in the request payload
            $existing = $this->read($id);
            $currentData = $existing[0] ?? [];

            // Merge existing data with incoming updates and enforce ID
            $mergedPayload = array_merge($currentData, $payload, ['id' => (int)$id]);

            $this->db->save($this->tbl, $mergedPayload);
        }

        return ['result' => $this->read($id)];
    }

    public function read(string $id = ''): array
    {
        try {
            $conditions = [
                'tbl'   => $this->tbl,
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
            $this->db->delete($this->tbl, ['id' => $id]);
            return ['status' => 'deleted', 'id' => $id];
        } catch (Throwable $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}