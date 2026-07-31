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
}