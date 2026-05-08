<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;
use RuntimeException;

class JobModel extends BaseModel
{
    protected string $table = 'jobs';

    /**
     * Create a new neural ingestion job record.
     */
    public function create(string $title, string $type, array $payload): int
    {
        $data = [
            'title'        => $title,
            'type'         => $type,
            'payload'      => json_encode($payload),
            'status'       => 'pending',
            'current_step' => 'init',
            'progress'     => 0,
            'created_at'   => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }

    /**
     * Retrieve a job by ID for the SPA poller.
     */
    public function getStatus(int $id): ?array
    {
        $result = $this->find($id);
        if ($result && isset($result['payload'])) {
            $result['payload'] = json_decode($result['payload'], true);
        }
        return $result;
    }

    /**
     * Update the progress and step (e.g., 'chunking', 'embedding').
     * Used by the JobService to sync with the Python Worker's progress.
     */
    public function updateProgress(int $id, int $progress, string $step, string $status = 'processing'): bool
    {
        return $this->update($id, [
            'progress'     => $progress,
            'current_step' => $step,
            'status'       => $status
        ]);
    }

    /**
     * Mark a job as finished.
     */
    public function complete(int $id): bool
    {
        return $this->update($id, [
            'status'      => 'completed',
            'progress'    => 100,
            'finished_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Mark a job as failed with a reason in the status.
     */
    public function fail(int $id, string $error): bool
    {
        return $this->update($id, [
            'status'       => 'failed',
            'current_step' => 'error',
            'payload'      => json_encode(['error' => $error])
        ]);
    }
}