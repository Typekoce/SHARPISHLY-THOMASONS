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
    public function prepare(): void
    {
        $jobId = $this->request('job_id');

        if (!is_string($jobId) || !preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $jobId)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid job_id'
            ], 400);

            return;
        }

        try {
            $service = $this->preparationService();
            $result = $service->prepare($jobId);

            $this->json([
                'success' => true,
                'job_id' => $jobId,
                'status' => $result['status']
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'error' => 'Invalid application identifier'
            ], 400);
        } catch (\RuntimeException $e) {
            $this->logger->error($e->getMessage());

            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 409);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());

            $this->json([
                'success' => false,
                'error' => 'Application preparation failed'
            ], 500);
        }
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
}