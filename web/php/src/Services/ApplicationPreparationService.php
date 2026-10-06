<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\JobApplicationRepository;
use App\Repositories\CvRepository;
use App\Repositories\ApplicationStateRepository;
use RuntimeException;
use Throwable;

final class ApplicationPreparationService
{
    public function __construct(
        private JobApplicationRepository $applications,
        private CvRepository $cvRepository,
        private PromptService $promptService,
        private AtomicJsonWriter $writer,
        private ApplicationStateRepository $states,
        private ApplicationLock $locks
    ) {
    }

    public function prepare(string $jobId): array
    {
        $application = $this->applications->findById($jobId);
        if ($application === null) {
            throw new RuntimeException('Vacancy not found');
        }

        $vacancy = $application['vacancy'];
        $directory = $application['directory'];

        $lock = $this->locks->acquire($directory);

        try {
            $currentState = $this->states->read($directory)['status'] ?? 'pending';
            if ($currentState !== 'pending' && $currentState !== 'failed') {
                throw new RuntimeException("Invalid state for preparation: {$currentState}");
            }

            $this->states->transition($directory, $currentState, 'processing');

            $sourceCv = $this->cvRepository->loadPrimary();

            $context = [
                'job_id' => $jobId,
                'vacancy' => $vacancy,
                'source_cv' => $sourceCv
            ];

            $promptContext = $this->promptService->buildContext($context);
            $tailored = $this->promptService->generate($promptContext);

            $this->validateOutput($tailored);

            // Persist reproducible prompt context separately
            $contextPath = $directory
                . DIRECTORY_SEPARATOR
                . 'generated'
                . DIRECTORY_SEPARATOR
                . 'prompt_context.json';
            $this->writer->write($contextPath, $promptContext);

            // Persist generated results
            $outputPath = $directory . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . 'tailored_cv.json';
            $this->writer->write($outputPath, [
                'job_id' => $jobId,
                'generated_at' => gmdate('c'),
                'tailored_cv' => $tailored['tailored_cv'] ?? [],
                'cover_letter' => $tailored['cover_letter'] ?? '',
                'evidence' => $tailored['evidence'] ?? []
            ]);

            $this->states->transition($directory, 'processing', 'review');

            return ['status' => 'review'];

        } catch (Throwable $e) {
            $this->states->fail($directory, $e->getMessage());
            throw $e;
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    private function validateOutput(array $output): void
    {
        if (!isset($output['tailored_cv']) || !is_array($output['tailored_cv'])) {
            throw new RuntimeException('tailored_cv must be an object');
        }

        if (!isset($output['cover_letter']) || !is_string($output['cover_letter'])) {
            throw new RuntimeException('cover_letter must be a string');
        }

        if (!isset($output['evidence']) || !is_array($output['evidence'])) {
            throw new RuntimeException('evidence must be an array');
        }

        foreach ($output['evidence'] as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Invalid evidence item');
            }

            if (
                !isset($item['claim']) ||
                !is_string($item['claim']) ||
                !isset($item['evidence_ids']) ||
                !is_array($item['evidence_ids'])
            ) {
                throw new RuntimeException('Malformed evidence item');
            }

            foreach ($item['evidence_ids'] as $evidenceId) {
                if (!is_string($evidenceId)) {
                    throw new RuntimeException('Evidence IDs must be strings');
                }
            }
        }
    }
}