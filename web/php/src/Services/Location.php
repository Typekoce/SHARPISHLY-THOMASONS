<?php

declare(strict_types=1);

namespace App\Services;

/**
 * THOMASONS V3 – Location Service (Native SSOT Edition)
 * Centralized path management for Infrastructure & Storage.
 */
class Location {
    private string $storageBase;
    private string $projectRoot;


    public function __construct() {
        // 1. Establish Project Root (The Source of Truth)
        if (defined('PROJECT_ROOT')) {
            $this->projectRoot = PROJECT_ROOT;
        } else {
            // Robust absolute calculation down from web/php/src/Services/Location.php
            $calculated = realpath(dirname(__DIR__, 4));
            $this->projectRoot = $calculated ?: rtrim(dirname(__DIR__, 4), '/');
        }

        // 2. Establish Storage Base
        $this->storageBase = $this->projectRoot . '/storage/';
        
        // Ensure the base storage exists immediately
        if (!is_dir($this->storageBase)) {
            mkdir($this->storageBase, 0775, true);
        }
    }

    public function baseDir(): string
    {
        return defined('PROJECT_ROOT') ? PROJECT_ROOT . '/' : dirname(__DIR__, 3) . '/';
    }

    /**
     * Task 2.1: Absolute path to the vector storage directory
     */
    public static function vectorStorage(): string {
        // Adjust the levels based on your actual file depth to reach root/storage/vectors
        return dirname(__DIR__, 2) . '/storage/vectors';
    }

    /**
     * STORAGE ENGINE: Handles dynamic pathing for data.
     * Includes auto-directory creation to prevent "Permission Denied" crashes.
     */
    public function storage(string $path = ''): string {
        $fullPath = $this->storageBase . ltrim($path, '/');
        
        // Logic to differentiate between a file path and a directory path
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        if ($extension === '' && !empty($path)) {
            $dir = rtrim($fullPath, '/') . '/';
            // Critical: Recursive directory creation
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            return $dir;
        }
        
        return $fullPath;
    }

    // --- Specific Domain Accessors ---
    public function nats(string $file = ''): string { return $this->storage('uploads/nats/' . ltrim($file, '/')); }
    public function uploads(string $file = ''): string { return $this->storage('uploads/' . ltrim($file, '/')); }
    public function queue(string $file = ''): string   { return $this->storage('queue/' . ltrim($file, '/')); }
    public function logs(string $file = ''): string    { return $this->storage('logs/' . ltrim($file, '/')); } 
    public function reports(string $file = ''): string { return $this->storage('reports/' . ltrim($file, '/')); }
    public function db(string $file = ''): string      { return $this->storage('database/' . ltrim($file, '/')); }

    public function relative(string $absolutePath): string {
        return str_replace($this->storageBase, '', $absolutePath);
    }

    /**
     * INFRASTRUCTURE ENGINE: Core configuration access.
     */
    public function makefile(): string       { return $this->projectRoot . '/Makefile'; }
    public function env(): string            { return $this->projectRoot . '/.env'; }
    public function nginxConfig(): string    { return $this->projectRoot . '/infra/nginx/default.conf'; }

    /**
     * Home directory hard-coded for now!
     */
    public function home($path = ''){
        return "/home/seaview/" . $path;
    }

}// end of class
