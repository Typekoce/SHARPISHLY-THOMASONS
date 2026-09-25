<?php
declare(strict_types=1);

namespace App\Controllers;

use Throwable;

class JobApplyController extends BaseController
{
    /**
     * Handles candidate job application requests across the abstraction layer.
     */
    public function index(): void
    {
        $data = [];

        $jobId         = $this->request('job_id');
        $board         = $this->request('board') ?? 'generic_board';
        $candidateData = $this->request('candidate') ?? [];

        try {
            // Populate data array safely using setData
            $data = $this->setData($data, 'job_id', $jobId);
            $data = $this->setData($data, 'board', $board);
            $data = $this->setData($data, 'candidate', $candidateData);
            $data = $this->setData($data, 'timestamp', $this->timestamp());

            $this->logger->info("JobApplyController: Processing application request for Job ID [{$jobId}] on board [{$board}]");

            // Construct abstraction layer payload conditions for ORM execution
            $conditions = [
                'source' => 'JobApplyAbstraction',
                'method' => 'POST',
                'data'   => $data,
            ];

            // Dispatch through the framework ORM service layer
            $response = $this->orm->execute($conditions);

            //$data['result'] = $response;
            $data = $this->setData($data, 'result', $response);
            
            $this->json([
                'status'  => 'success',
                'message' => 'Job application successfully routed through the abstraction layer.',
                'data'    => $data
            ]);

        } catch (Throwable $e) {

            $this->logger->error("JobApplyController Error: " . $e->getMessage());
            
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}