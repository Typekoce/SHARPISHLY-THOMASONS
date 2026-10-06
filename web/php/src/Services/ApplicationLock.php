<?php
declare(strict_types=1);

namespace App\Services;

final class ApplicationLock
{
    public function acquire(string $directory)
    {
        $lockPath = $directory . DIRECTORY_SEPARATOR . '.application.lock';
        $handle = fopen($lockPath, 'c');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open application lock');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new \RuntimeException('Application is already being processed');
        }

        return $handle;
    }
}