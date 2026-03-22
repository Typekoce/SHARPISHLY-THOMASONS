<?php
declare(strict_types=1);

namespace App\Models;

use App\Registry;
use App\Db;

class LandlordModel
{
    private Db $db;

    public function __construct()
    {
        $this->db = Registry::get(Db::class);
    }

    /**
     * Get all properties ordered by latest
     */
    public function getAll(): array
    {
        return $this->db->find([
            'tbl'   => 'properties',
            'order' => ['id' => 'DESC']
        ]);
    }

    /**
     * Save property data
     */
    public function saveProperty(array $data): bool
    {
        return (bool)$this->db->save(array_merge(['tbl' => 'properties'], $data));
    }
}