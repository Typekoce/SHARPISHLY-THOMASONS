<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use App\Services\Logger;

/**
 * BASE MODEL
 * Parent for all models. Handles database service injection.
 */
class BaseModel {

    protected Db $db;

    public function __construct()
    {
        /**
         * MISSION: Zero Globals (Internal Fallback).
         * We fetch the environment and instantiate the DB.
         * If a global logger exists, we use it; otherwise, we stay silent.
         */
        $logger = $GLOBALS['logger'] ?? new Logger();
        $config = get_env(); 

        // Direct assignment to satisfy the Type Hinting
        $this->db = new Db($config, $logger);
    }
}