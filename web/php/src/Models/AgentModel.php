<?php

declare(strict_types=1);

namespace App\Models;

class AgentModel extends BaseModel
{
    private string $table = 'agents';

    /**
     * Retrieve all agents ordered by latest
     */
    public function all(): array
    {
        return $this->db->find([
            'tbl'   => $this->table,
            'order' => ['id' => 'DESC']
        ]);
    }

    /**
     * Find a single agent record by ID
     */
    public function find(int $id): ?array
    {
        $results = $this->db->find([
            'tbl'   => $this->table,
            'where' => ['id' => $id],
            'limit' => 1
        ]);

        return $results[0] ?? null;
    }

    /**
     * Create or update an agent record matching ScaffoldModel schema
     */
    public function create(array $data): int|bool
    {
        return $this->db->save($this->table, $data);
    }

    /**
     * Update an agent by ID
     */
    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $result = $this->db->save($this->table, $data);
        return $result !== false;
    }

    /**
     * Delete an agent by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `id` = ?";
        return $this->db->execute($sql, [$id]);
    }

    // Inside AgentModel.php (Model Layer)

    public function claimNextPending(): ?array
    {
        // 1. Find the oldest pending record via Model abstraction
        $pending = $this->where('status', 'pending')
                        ->orderBy('id', 'ASC')
                        ->first();

        if (!$pending) {
            return null;
        }

        // 2. Atomically update status to prevent double-execution
        $updated = $this->update($pending['id'], [
            'status'     => 'running',
            'claimed_at' => date('Y-m-d H:i:s')
        ]);

        if (!$updated) {
            return null;
        }

        $pending['status'] = 'running';
        return $pending;
    }
}