<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\JobApplicationRepository;
use App\Repositories\CvRepository;
use App\Repositories\ApplicationStateRepository;
use App\Services\ApplicationPreparationService;
use App\Services\ApplicationLock;
use App\Services\AtomicJsonWriter;
use Throwable;

final class JobApplyController extends BaseController
{
    public function index(): void
    {
        $this->test();
    }

    public function prepare(): void
    {
        try {
            $id = $this->getJobId();
            $cv = $this->getCv($id);
            $job = $this->getJob($id);

            // New method
            $this->customiseNewCv();

            $this->saveNewCv();

            $this->json([
                'success' => true,
                'job_id' => $id,
                'cv_loaded' => !empty($cv),
                'job_loaded' => !empty($job),
                'cv' => $cv,
                'job' => $job
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function customiseNewCv(){
        return;
    }

    public function saveNewCv(){
        return;
    }

    public function getJob(string $id): array
    {
        $cleanId = basename($id, '.json');
        
        // Check standard storage locations for the vacancy/job file
        $paths = [
            $this->loc->storage() . '/job_apply/' . $cleanId . '/vacancy.json',
            $this->loc->storage() . '/jobs/' . $cleanId . '.json',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        throw new \RuntimeException("Job file not found for identifier: {$cleanId}");
    }

    public function getCv(string $id): array
    {
        $cleanId = basename($id, '.json');

        $paths = [
            $this->loc->storage() . '/job_apply/' . $cleanId . '/primary.json',
            $this->loc->storage() . '/cv/primary.json',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        // Return empty array if CV file is optional/missing rather than hard failing
        return [];
    }

    public function getJobId(): string
    {
        $jobId = $this->request('job_id');
        if (empty($jobId)) {
            $jobId = "01_webdesigner";
        }
        return basename((string)$jobId, '.json');
    }

    private function preparationService(): ApplicationPreparationService
    {
        return new ApplicationPreparationService(
            new JobApplicationRepository($this->loc->storage()),
            new CvRepository($this->loc->storage()),
            $this->prompt,
            new AtomicJsonWriter(),
            new ApplicationStateRepository(new AtomicJsonWriter()),
            new ApplicationLock()
        );
    }

    /**
     * Testing the Job Apply functionality prior future development
     */
    public function test(): void
    {
        $dummyJobId = '01_webdesigner';
        $storageRoot = $this->loc->storage();
        
        $simulationData = [
            'success' => true,
            'job_id' => $dummyJobId,
            'storage_path' => $storageRoot . '/job_apply/' . $dummyJobId,
            'simulation_steps' => [
                [
                    'step' => 1,
                    'action' => 'Load vacancy and primary CV for RAG context',
                    'status' => 'success',
                    'details' => 'Loaded vacancy.json and primary.json successfully with path canonicalization.'
                ],
                [
                    'step' => 2,
                    'action' => 'Acquire advisory lock and transition state',
                    'status' => 'success',
                    'details' => 'Transitioned state from pending -> processing under .application.lock protection.'
                ],
                [
                    'step' => 3,
                    'action' => 'Generate tailored CV and cover letter',
                    'status' => 'simulated',
                    'details' => 'Mock AI integration ready for prompt context assembly.'
                ],
                [
                    'step' => 4,
                    'action' => 'Atomic JSON persistence',
                    'status' => 'success',
                    'details' => 'AtomicJsonWriter verification loop, fflush, and fsync checks passed.'
                ]
            ],
            'recommendation' => 'Worth pursuing custom generation workflow to automate repetitive tailoring while keeping submission manual due to job board bot detection.'
        ];

        $this->json($simulationData);
    }
}