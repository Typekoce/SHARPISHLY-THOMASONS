<?php
namespace App\Services;

use App\Services\Adapters\ImapAdapter;
use App\Services\Adapters\SmtpAdapter;

class MailService
{
    public function send(string $to, string $subject, string $body, ?string $provider = null): void
    {
        // Choose provider-specific SMTP settings (via env)
        $smtp = new SmtpAdapter($this->smtpConfig($provider));
        $smtp->send($to, $subject, $body);
    }

    public function fetch(string $provider, string $folder = 'INBOX', int $limit = 25): array
    {
        $imap = new ImapAdapter($this->imapConfig($provider));
        return $imap->fetch($folder, $limit);
    }

    protected function smtpConfig(?string $provider): array
    {
        // Map provider -> SMTP config; pull credentials from env
        $p = $provider ?? getenv('DEFAULT_MAIL_PROVIDER') ?: 'gmail';
        switch (strtolower($p)) {
            case 'gmail':
                return [
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'secure' => 'tls',
                    'username' => getenv('GMAIL_USER'),
                    'password' => getenv('GMAIL_PASS'),
                    'from' => getenv('GMAIL_FROM') ?: getenv('GMAIL_USER'),
                ];
            case 'hotmail':
            case 'outlook':
                return [
                    'host' => 'smtp.office365.com',
                    'port' => 587,
                    'secure' => 'tls',
                    'username' => getenv('HOTMAIL_USER'),
                    'password' => getenv('HOTMAIL_PASS'),
                    'from' => getenv('HOTMAIL_FROM') ?: getenv('HOTMAIL_USER'),
                ];
            case 'zoho':
                return [
                    'host' => 'smtp.zoho.com',
                    'port' => 587,
                    'secure' => 'tls',
                    'username' => getenv('ZOHO_USER'),
                    'password' => getenv('ZOHO_PASS'),
                    'from' => getenv('ZOHO_FROM') ?: getenv('ZOHO_USER'),
                ];
            default:
                throw new \InvalidArgumentException("Unknown provider: $provider");
        }
    }

    protected function imapConfig(string $provider): array
    {
        $p = strtolower($provider);
        switch ($p) {
            case 'gmail':
                return [
                    'host' => 'imap.gmail.com',
                    'port' => 993,
                    'flags' => '/imap/ssl',
                    'username' => getenv('GMAIL_USER'),
                    'password' => getenv('GMAIL_PASS'),
                ];
            case 'hotmail':
            case 'outlook':
                return [
                    'host' => 'outlook.office365.com',
                    'port' => 993,
                    'flags' => '/imap/ssl',
                    'username' => getenv('HOTMAIL_USER'),
                    'password' => getenv('HOTMAIL_PASS'),
                ];
            case 'zoho':
                return [
                    'host' => 'imap.zoho.com',
                    'port' => 993,
                    'flags' => '/imap/ssl',
                    'username' => getenv('ZOHO_USER'),
                    'password' => getenv('ZOHO_PASS'),
                ];
            default:
                throw new \InvalidArgumentException("Unknown provider: $provider");
        }
    }
}
