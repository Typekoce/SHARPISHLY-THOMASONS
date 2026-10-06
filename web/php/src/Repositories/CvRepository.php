<?php
declare(strict_types=1);

namespace App\Repositories;

final class CvRepository
{
    public function __construct(private string $storageRoot)
    {
    }

    public function loadPrimary(): array
    {
        $path = rtrim($this->storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cv' . DIRECTORY_SEPARATOR . 'primary.json';
        $realPath = realpath($path);

        if ($realPath === false || !is_file($realPath)) {
            return [];
        }

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read primary CV');
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}