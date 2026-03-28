<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use Exception;

/**
 * SCAFFOLD MODEL
 * Use this as a blueprint for interacting with the database.
 */
class BaseModel {

    private Db $db;
    private string $table = 'scaffold_items'; // Change this to your table name

    public function __construct()
    {
        $this->db = $GLOBALS['db'] ?? new \App\Services\Db();
    }

}
