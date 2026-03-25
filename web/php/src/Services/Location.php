<?php
declare(strict_types=1);

namespace App\Services;

/**
 * THOMASONS V3 – Location Service
 * Centralized path management for the application.
 * Ensures all file operations target the /storage root outside the public web folder.
 */
class Location {
    // We derive the base from the APP_ROOT constant defined in bootstrap.php
    // This points to /var/www/html/storage/
    private string $base;

    public function __construct() {
        // Fallback to a hardcoded path if APP_ROOT isn't defined, 
        // but ideally uses the dynamic root.
        $this->base = defined('APP_ROOT') ? APP_ROOT . 'storage/' : '/var/www/html/storage/';
    }

    /**
     * Standard project root
     */
    public function baseDir(): string {
        return defined('APP_ROOT') ? APP_ROOT : dirname($this->base) . '/';
    }

    /**
     * General storage path helper
     */
    public function storage(string $path = ''): string {
        $fullPath = $this->base . ltrim($path, '/');
        // Ensure trailing slash for directories if no filename is provided
        return (pathinfo($fullPath, PATHINFO_EXTENSION) === '') 
            ? rtrim($fullPath, '/') . '/' 
            : $fullPath;
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