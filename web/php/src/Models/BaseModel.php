<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;

/**
 * BASE MODEL
 * Parent for all models. Handles database service injection.
 */
class BaseModel {

    protected Db $db; // Must be protected so child classes can access it

    public function __construct()
    {
        /**
         * MISSION: Zero Globals. 
         * We instantiate the Db service directly. 
         * Your Db class already handles its own PDO connection.
         */
        $this->db = new Db();
    }
}