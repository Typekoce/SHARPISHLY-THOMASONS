<?php

namespace App\Security;

class Security {
    public static function applyHeaders() {
        if (!headers_sent()) {
            // Prevent Clickjacking
            header("X-Frame-Options: DENY");
            // Prevent MIME sniffing
            header("X-Content-Type-Options: nosniff");
            // Enable XSS protection
            header("X-XSS-Protection: 1; mode=block");
            // Strict Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';");
        }
    }
}