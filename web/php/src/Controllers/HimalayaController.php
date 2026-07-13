<?php

namespace App\Controllers;

use RuntimeException;

class HimalayaController extends BaseController {
    public function setup(): void {
        $accounts = [
            ['name' => 'work', 'email' => 'user@work.com', 'imap' => 'imap.example.com'],
            ['name' => 'personal', 'email' => 'user@gmail.com', 'imap' => 'imap.example.com']
        ];

        $path = $this->loc->home('.config/himalaya/config.toml');
        $dir = dirname($path);

        $this->ensureSecurePath($dir, $path);
        
        $toml = $this->generateConfig($accounts);

        if (file_put_contents($path, $toml, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write Himalaya config.");
        }

        // Enforce permissions after write to override umask
        chmod($dir, 0700);
        chmod($path, 0600);
    }

    private function ensureSecurePath(string $dir, string $path): void {
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create config directory.");
        }
        // Ensure directory has correct mode if it already existed
        chmod($dir, 0700);
    }

    private function generateConfig(array $accounts): string {
        $toml = "";
        foreach ($accounts as $creds) {
            $toml .= "[accounts.{$creds['name']}]\n";
            $toml .= "email = \"{$creds['email']}\"\n";
            $toml .= "imap-host = \"{$creds['imap']}\"\n\n";
        }
        return $toml;
    }
}