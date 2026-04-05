<?php

declare(strict_types=1);

namespace App\Services;

/**
 * THOMASONS V3 – Location Service (SSOT Edition)
 * Centralized path management for Infrastructure & Storage.
 */
class Location {
    private string $storageBase;
    private string $projectRoot;

    public function __construct() {
        // 1. Establish Storage Base (Docker-first priority)
        if (is_dir('/var/www/html/storage')) {
            $this->storageBase = '/var/www/html/storage/';
        } else {
            // Fallback for local dev environments
            $this->storageBase = defined('APP_ROOT') 
                ? APP_ROOT . 'storage/' 
                : dirname(__DIR__, 4) . '/storage/';
        }

        // 2. Establish Project Root (The folder containing Makefile/docker-compose)
        // Based on tree: web/php/src/Services/Location.php
        $this->projectRoot = realpath(dirname(__DIR__, 4)) ?: '';
    }

    /**
     * STORAGE ENGINE: Handles dynamic pathing for data.
     */
    public function storage(string $path = ''): string {
        $fullPath = $this->storageBase . ltrim($path, '/');
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        // If it's a directory (no extension), ensure trailing slash
        if ($extension === '' && !empty($path)) {
            return rtrim($fullPath, '/') . '/';
        }
        return $fullPath;
    }

    public function uploads(string $file = ''): string { return $this->storage('uploads/' . ltrim($file, '/')); }
    public function queue(string $file = ''): string   { return $this->storage('queue/' . ltrim($file, '/')); }
    public function logs(string $file = ''): string    { return $this->storage('log/' . ltrim($file, '/')); }
    public function reports(string $file = ''): string { return $this->storage('reports/' . ltrim($file, '/')); }
    public function db(string $file = ''): string      { return $this->storage('database/' . ltrim($file, '/')); }

    public function relative(string $absolutePath): string {
        return str_replace($this->storageBase, '', $absolutePath);
    }

    /**
     * INFRASTRUCTURE ENGINE: Returns paths to core configuration files.
     */
    public function makefile(): string       { return $this->projectRoot . '/Makefile'; }
    public function dockerCompose(): string  { return $this->projectRoot . '/docker-compose.yml'; }
    public function dockerfile(): string     { return $this->projectRoot . '/Dockerfile'; }
    public function env(): string            { return $this->projectRoot . '/.env'; }
    public function nginxConfig(): string    { return $this->projectRoot . '/infra/nginx/default.conf'; }
    public function aiRequirements(): string { return $this->projectRoot . '/ai/requirements.txt'; }
}