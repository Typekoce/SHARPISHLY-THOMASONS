<?php
declare(strict_types=1);

namespace App\Services;

/**
 * THOMASONS V3 – Location Service
 * Centralized path management for the application.
 * Ensures all file operations target the /storage root outside the public web folder.
 */
class Location {
    /**
     * The absolute path to the storage directory.
     * In your Docker environment, this is /var/www/html/storage/
     */
    private string $base;

    public function __construct() {
        // We prioritize the absolute Docker path to ensure Web and Worker are synced.
        // Fallback to APP_ROOT only if we are running in a different environment.
        if (is_dir('/var/www/html/storage')) {
            $this->base = '/var/www/html/storage/';
        } else {
            $this->base = defined('APP_ROOT') ? APP_ROOT . 'storage/' : dirname(__DIR__, 4) . '/storage/';
        }
    }

    /**
     * Standard project root
     */
    public function baseDir(): string {
        return dirname($this->base) . '/';
    }

    /**
     * General storage path helper
     */
    public function storage(string $path = ''): string {
        $fullPath = $this->base . ltrim($path, '/');
        
        // Check if the path ends in a filename or is a directory
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        if ($extension === '' && !empty($path)) {
            return rtrim($fullPath, '/') . '/';
        }
        
        return $fullPath;
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
    
    public function reports(string $file = ''): string {
        return $this->storage('reports/' . ltrim($file, '/'));
    }

    public function templates(string $file = ''): string {
        return $this->storage('templates/' . ltrim($file, '/'));
    }

    /**
     * Helper for the Mock DB path
     */
    public function db(string $file = 'db.json'): string {
        return $this->storage('database/' . ltrim($file, '/'));
    }

    /**
     * Strips the base storage path to return a relative link
     */
    public function relative(string $absolutePath): string {
        return str_replace($this->base, '', $absolutePath);
    }
}