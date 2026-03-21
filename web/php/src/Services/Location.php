<?php
// Location: php/src/Services/Location.php

namespace App\Services;

class Location {
    private string $base = '/var/www/html/storage/';

    /**
     * Standard project root derived from storage path
     */
    public function baseDir(): string {
        return dirname($this->base) . '/';
    }

    /**
     * RESTORED: General storage path helper
     */
    public function storage(string $path = ''): string {
        return $this->base . ltrim($path, '/');
    }

    public function uploads(string $file = ''): string {
        return $this->storage('uploads/' . ltrim($file, '/'));
    }

    public function queue(string $file = ''): string {
        return $this->storage('queue/' . ltrim($file, '/'));
    }

    public function logs(string $file = ''): string {
        return $this->storage('logs/' . ltrim($file, '/'));
    }
    
    public function relative(string $absolutePath): string {
        return str_replace($this->base, '', $absolutePath);
    }
}
