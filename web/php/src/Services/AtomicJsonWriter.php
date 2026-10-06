<?php
declare(strict_types=1);

namespace App\Services;

final class AtomicJsonWriter
{
    public function write(string $path, array $data): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
            throw new \RuntimeException('Unable to create output directory');
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $temporary = tempnam($directory, '.write-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create temporary file');
        }

        try {
            $handle = fopen($temporary, 'wb');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open temporary file');
            }

            try {
                $length = strlen($json);
                $written = 0;

                while ($written < $length) {
                    $result = fwrite($handle, substr($json, $written));
                    if ($result === false || $result === 0) {
                        throw new \RuntimeException('Unable to write JSON');
                    }
                    $written += $result;
                }

                if (!fflush($handle)) {
                    throw new \RuntimeException('Unable to flush JSON');
                }

                if (function_exists('fsync') && !fsync($handle)) {
                    throw new \RuntimeException('Unable to synchronise JSON');
                }
            } finally {
                fclose($handle);
            }

            if (!rename($temporary, $path)) {
                throw new \RuntimeException('Unable to replace JSON file');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}