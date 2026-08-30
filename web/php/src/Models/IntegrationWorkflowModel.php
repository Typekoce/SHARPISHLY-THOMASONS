<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class IntegrationWorkflowModel extends BaseModel
{
    public bool $debug = false;

    public string $tbl = 'integration_workflows';

    public function create(array $payload = []): array
    {
        if (empty($payload)) {
            $payload = [
                'title'         => 'Default Roster API Sync Task',
                'source_system' => 'CareRoster-API',
                'target_system' => 'EmbraceCare-Core',
                'payload'       => json_encode(['sync_type' => 'automated_roster_ingest']),
                'status'        => 'pending'
            ];
        }

        if (isset($payload['payload']) && is_array($payload['payload'])) {
            $payload['payload'] = json_encode($payload['payload']);
        }

        $this->db->save($this->tbl, $payload);
        return ['result' => $this->read()];
    }

    public function update(string $id = '', array $payload = []): array
    {
        if (!empty($id)) {
            $existing = $this->read($id);
            $currentData = $existing[0] ?? [];

            // Prevent overwriting existing payload with null if omitted in partial update
            if (isset($payload['payload']) && is_array($payload['payload'])) {
                $payload['payload'] = json_encode($payload['payload']);
            }

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
                $logger->error("IntegrationWorkflowModel::read failed: " . $e->getMessage());
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
