<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ScaffoldModel;
use Throwable;

class ScaffoldController extends BaseController
{
    public function migrate(): void
    {
        try {
            $scaffold = new ScaffoldModel();
            $applied = $scaffold->syncSchema();

            $this->json([
                'status'    => 'success',
                'applied'   => $applied,
                'db_type'   => 'MySQL 8.0 (Docker)',
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Throwable $e) {
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}