<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use Throwable;

class HealthModel
{
    private ?Db $db = null;

    /**
     * Checks if the database service is initialized and responding.
     * Uses the Service's internal column check to avoid raw SQL.
     */
    public function isDatabaseReady(): bool
    {
        try {
            // Lazy load the DB service to handle connection exceptions gracefully
            if ($this->db === null) {
                $this->db = new Db();
            }

            // If we can verify a column exists in a system table (or any table), 
            // the connection and permissions are fully functional.
            // We use 'information_schema.COLUMNS' as a reliable target.
            return $this->db->columnExists('information_schema.TABLES', 'TABLE_NAME');
            
        } catch (Throwable $e) {
            // We log the failure but return false so the Controller can report 'degraded'
            error_log("HealthModel: DB Service unreachable - " . $e->getMessage());
            return false;
        }
    }
}