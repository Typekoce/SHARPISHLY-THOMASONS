<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ScaffoldModel;
use src\Services\Session;
use Throwable;

class ScaffoldController extends BaseController
{
    protected Session $session;

    public function __construct()
    {
        parent::__construct();
        $this->session = Session::getInstance();
    }

    /**
     * GET /php/scaffold/migrate
     * The unified migration endpoint for PyMVC and the Frontend.
     */
    public function migrate(): void
    {
        // 1. Session Gate - Fast in-memory check
        if ($this->session->get('migrated', false) === true) {
            $this->json([
                'status'  => 'skip',
                'message' => 'Schema already verified for this session.'
            ]);
            return;
        }

        try {
            $scaffold = new ScaffoldModel();
            
            // 2. Physical Gate - Check if the 'migrations' table has the latest record
            $lastMigration = 'v3_6_neural_pipeline_init';
            $alreadyRun = $this->db->find([
                'tbl'   => 'migrations',
                'where' => ['migration_name' => $lastMigration]
            ]);

            if (empty($alreadyRun)) {
                // 3. Execution Phase - Run the merged schema
                $applied = $scaffold->syncSchema();

                // Record the migration
                $this->db->save('migrations', [
                    'migration_name' => $lastMigration,
                    'batch'          => 1
                ]);

                $this->session->set('migrated', true);
                
                $this->json([
                    'status'    => 'success',
                    'applied'   => $applied,
                    'message'   => 'Schema migration completed successfully.'
                ]);
            } else {
                // Sync session if DB is already provisioned
                $this->session->set('migrated', true);
                $this->json([
                    'status'  => 'skip',
                    'message' => 'Database already up-to-date; session synchronized.'
                ]);
            }

        } catch (Throwable $e) {
            $this->session->set('migrated', false);
            $this->json([
                'status'  => 'error',
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
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
}