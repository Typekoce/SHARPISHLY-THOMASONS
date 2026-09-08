<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Orm;
use Throwable;

class NotificationsModel extends BaseModel
{
    public string $tbl = 'notifications';
    protected Orm $orm;

    private const ALLOWED_SOURCES = ['GoogleCal', 'Hotmail', 'Outlook', 'EventBriteHello', 'AzureHelloWorld'];

    public function __construct()
    {
        parent::__construct();
        $this->orm = new Orm();
    }

    /**
     * Safely fetches external notifications using the Orm service.
     */
    public function fetchExternalNotifications(string $source): array
    {
        if (!in_array($source, self::ALLOWED_SOURCES, true)) {
            return [
                'error'   => true,
                'message' => "Unsupported notification source: {$source}"
            ];
        }

        $response = $this->orm->execute([
            'source' => $source,
            'action' => 'read'
        ]);

        if (isset($response['error'])) {
            return [
                'error'   => true,
                'message' => "Failed to aggregate from {$source}: " . ($response['message'] ?? 'Unknown error')
            ];
        }

        $data = $response['data'] ?? $response;
        if (!is_array($data)) {
            $data = [$data];
        }

        return [
            'source' => $source,
            'data'   => $data
        ];
    }

    /**
     * Stores aggregated third-party notification items explicitly.
     */
    public function createFromAggregation(string $source, array $externalData): array
    {
        $payload = [
            'title'       => "Notification from {$source}",
            'description' => json_encode($externalData),
            'status'      => 'unread',
            'source'      => $source,
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $this->db->save($this->tbl, $payload);
        return ['result' => $this->read()];
    }

    /**
     * Explicit payload creation with required field validation.
     */
    public function create(array $payload = []): array
    {
        if (empty($payload['title'])) {
            return [
                'error'   => true,
                'message' => 'Notification title is required'
            ];
        }

        $payload['created_at'] ??= date('Y-m-d H:i:s');
        $payload['status']     ??= 'unread';

        $this->db->save($this->tbl, $payload);
        return ['result' => $this->read()];
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
                $logger->error("NotificationsModel::read failed: " . $e->getMessage());
            }

            return [
                'error'   => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(string $id = '', array $payload = []): array
    {
        if (empty($id)) {
            return ['error' => true, 'message' => 'Missing ID for update'];
        }

        $existing = $this->read($id);
        $currentData = $existing[0] ?? [];
        $mergedPayload = array_merge($currentData, $payload, ['id' => (int)$id]);

        $this->db->save($this->tbl, $mergedPayload);
        return ['result' => $this->read($id)];
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