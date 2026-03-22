<?php
declare(strict_types=1);

namespace App\Models;

use App\Registry;
use App\Db;
use Exception;

/**
 * SCAFFOLD MODEL
 * Use this as a blueprint for interacting with the database.
 */
class ScaffoldModel
{
    private Db $db;
    private string $table = 'scaffold_items'; // Change this to your table name

    public function __construct()
    {
        // Pull the shared DB instance from the Registry
        $this->db = Registry::get(Db::class);
    }

    /**
     * Retrieve items with structured parameters
     */
    public function getAll(): array
    {
        return $this->db->find([
            'tbl'    => $this->table,
            'fields' => ['id', 'name', 'status', 'created_at'],
            'order'  => ['id' => 'DESC']
        ]);
    }

    /**
     * Save or update an entry
     */
    public function store(array $data): bool
    {
        // Ensure the table name is passed to the Db abstraction
        $data['tbl'] = $this->table;
        
        return $this->db->save($data);
    }

    /**
     * Fetch a single item by ID
     */
    public function getById(int $id): array
    {
        $results = $this->db->find([
            'tbl'   => $this->table,
            'where' => ['id' => $id],
            'limit' => 1
        ]);

        return $results[0] ?? [];
    }
}