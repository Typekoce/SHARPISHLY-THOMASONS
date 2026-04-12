<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ScaffoldModel;
use Throwable;

class ScaffoldController extends BaseController
{
    /**
     * GET /php/scaffold/migrate
     * The unified migration endpoint for PyMVC and the Frontend.
     */
    public function migrate(): void
    {
        // 1. Session Gate remains the same
        if ($this->session->get('migrated', false) === true) {
            $this->json(['status' => 'skip', 'message' => 'Schema verified.']);
            return;
        }

        try {
            $scaffold = new ScaffoldModel();
            
            // --- THE FIX START ---
            // Ensure the 'migrations' table itself exists before we query it
            // We call syncSchema once to build the house before checking the records
            $scaffold->syncSchema(); 
            // --- THE FIX END ---

            $lastMigration = 'v3_6_neural_pipeline_init';
            
            // Now this find() will work because syncSchema just created the table
            $alreadyRun = $this->db->find([
                'tbl'   => 'migrations',
                'where' => ['migration_name' => $lastMigration]
            ]);

            if (empty($alreadyRun)) {
                // Record the fact that we are at v3.6
                $this->db->save('migrations', [
                    'migration_name' => $lastMigration,
                    'batch'          => 1
                ]);

                $this->session->set('migrated', true);
                $this->json([
                    'status'  => 'success',
                    'message' => 'Schema migration and migrations table initialized.'
                ]);
            } else {
                $this->session->set('migrated', true);
                $this->json(['status' => 'skip', 'message' => 'Already up to date.']);
            }

        } catch (Throwable $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Redis test submission
     * @param
     */
    public function redis(){
    // Simple Redis Task Pusher
    $redis = new Redis();
    $redis->connect('sharpishly-redis', 6379);

    $task = [
        'action' => 'process_upload',
        'file'   => $this->location->uploads('test.csv'),
        'ts'     => time()
    ];
    // $this->dBug($task);die();
    // LPUSH: Add to the start of the list
    $redis->lPush('task_queue', json_encode($task));

    echo "Task dispatched to Neural Pipeline.";
    }

    /**
     * Final Sunday Schema Fix
     */
    public function alter()
    {
        $this->db->alter([
            'jobs' => [
                'MODIFY' => [
                    'payload' => 'TEXT NULL DEFAULT NULL', // THE CRITICAL FIX
                    // 'status'  => "ENUM('pending','processing','completed','failed','archived') DEFAULT 'pending'"
                ],
                'ADD' => [
                    // 'embedding_version' => 'VARCHAR(50) DEFAULT NULL',
                    // 'processed_at'      => 'TIMESTAMP NULL DEFAULT NULL',
                    // 'error_message'     => 'TEXT NULL',
                    // 'finished_at'       => 'DATETIME NULL'
                ]
            ]
        ]);
        
        return $this->json(['status' => 'success', 'message' => 'Payload is now nullable. Job #1 should clear.']);
    }

}
