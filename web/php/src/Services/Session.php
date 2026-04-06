<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Session Service - Singleton Pattern
 * Handles state persistence for the Neural Pipeline
 */
class Session
{
    private static ?self $instance = null;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => false, // Set to true if using HTTPS
                'use_strict_mode' => true
            ]);
        }
    }

    /**
     * Get the Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set a session value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Clear specific key or destroy session
     */
    public function clear(?string $key = null): void
    {
        if ($key) {
            unset($_SESSION[$key]);
        } else {
            session_unset();
            session_destroy();
        }
    }
}