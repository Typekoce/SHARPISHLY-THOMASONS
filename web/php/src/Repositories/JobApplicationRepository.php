<?php
declare(strict_types=1);

namespace App\Repositories;

final class JobApplicationRepository
{
    private string $storageRoot;

    public function __construct(string $storageRoot)
    {
        $realRoot = realpath($storageRoot);

        if ($realRoot === false || !is_dir($realRoot)) {
            throw new \RuntimeException('Invalid storage root path');
        }

        $jobApplyRoot = $realRoot
            . DIRECTORY_SEPARATOR
            . 'job_apply';

        if (!is_dir($jobApplyRoot)) {
            throw new \RuntimeException('Job application storage is unavailable');
        }

        $resolvedJobApplyRoot = realpath($jobApplyRoot);

        if ($resolvedJobApplyRoot === false || !is_dir($resolvedJobApplyRoot)) {
            throw new \RuntimeException('Invalid job application storage path');
        }

        $this->storageRoot = rtrim($resolvedJobApplyRoot, DIRECTORY_SEPARATOR);
    }

    public function findById(string $jobId): ?array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $jobId)) {
            throw new \InvalidArgumentException('Invalid job ID format');
        }

        $directory = $this->storageRoot
            . DIRECTORY_SEPARATOR
            . $jobId;

        $resolvedDirectory = realpath($directory);

        if (
            $resolvedDirectory === false ||
            !is_dir($resolvedDirectory) ||
            !$this->isInside($resolvedDirectory, $this->storageRoot)
        ) {
            return null;
        }

        $vacancyPath = $resolvedDirectory . DIRECTORY_SEPARATOR . 'vacancy.json';
        $resolvedVacancy = realpath($vacancyPath);

        if (
            $resolvedVacancy === false ||
            !is_file($resolvedVacancy) ||
            !$this->isInside($resolvedVacancy, $resolvedDirectory)
        ) {
            return null;
        }

        $contents = file_get_contents($resolvedVacancy);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read vacancy file');
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return [
            'id' => $jobId,
            'directory' => $resolvedDirectory,
            'vacancy' => $data
        ];
    }

    private function isInside(string $path, string $base): bool
    {
        $prefix = $base . DIRECTORY_SEPARATOR;
        return strncmp($path, $prefix, strlen($prefix)) === 0;
    }
}