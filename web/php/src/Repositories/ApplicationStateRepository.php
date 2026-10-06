<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\AtomicJsonWriter;

final class ApplicationStateRepository
{
    public function __construct(
        private AtomicJsonWriter $writer
    ) {
    }

    public function read(string $directory): array
    {
        $path = $directory . DIRECTORY_SEPARATOR . 'state.json';

        if (!is_file($path)) {
            return [
                'status' => 'pending',
                'approved' => false,
                'submitted' => false
            ];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read application state');
        }

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    public function transition(
        string $directory,
        string $expected,
        string $next
    ): array {
        $state = $this->read($directory);
        $current = $state['status'] ?? 'pending';

        if ($current !== $expected) {
            throw new \RuntimeException(
                "Invalid state transition: {$current} -> {$next}"
            );
        }

        $state['status'] = $next;
        $state['updated_at'] = gmdate('c');

        $this->writer->write(
            $directory . DIRECTORY_SEPARATOR . 'state.json',
            $state
        );

        return $state;
    }

    public function fail(string $directory, string $message): void
    {
        try {
            $state = $this->read($directory);
            $state['status'] = 'failed';
            $state['error'] = $message;
            $state['updated_at'] = gmdate('c');

            $this->writer->write(
                $directory . DIRECTORY_SEPARATOR . 'state.json',
                $state
            );
        } catch (\Throwable) {
            // Suppress secondary exceptions during failure logging
        }
    }
}