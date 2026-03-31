<?php
declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Gmail;
use Google\Service\Drive;
use RuntimeException;
use Exception;

/**
 * GOOGLE SERVICE LAYER – THOMASONS V3
 * DRY integration for Google Calendar, Gmail, and Drive APIs.
 * Follows the exact same style as your BaseService / other services.
 *
 * SETUP (one-time):
 * 1. composer require google/apiclient:^2.15
 * 2. Google Cloud Console → Enable: Calendar API, Gmail API, Drive API
 * 3. Create "OAuth 2.0 Client IDs" (Web application) → Authorized redirect URIs must include your callback, e.g. https://yourdomain.com/php/google/callback
 * 4. Download the JSON → save as: storage/google/credentials.json
 * 5. First visit will require OAuth consent (see example controller usage below).
 */
class GoogleService extends BaseService
{
    protected Client $client;
    protected string $tokenPath;
    protected string $credentialsPath;

    public function __construct()
    {
        parent::__construct(); // inherits logging, location, uploadPath, etc.

        // Storage paths (reuses your Location service)
        $googleStorage = $this->location->storage('google');
        $this->ensureDirectoryExists($googleStorage);

        $this->tokenPath       = $googleStorage . '/token.json';
        $this->credentialsPath = $googleStorage . '/credentials.json';

        if (!file_exists($this->credentialsPath)) {
            throw new RuntimeException(
                "GoogleService: credentials.json not found at {$this->credentialsPath}. " .
                "Download it from Google Cloud Console and place it there."
            );
        }

        $this->initializeClient();
    }

    /**
     * Private helper (copied from BaseService so we don't modify existing files)
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException("GoogleService: Failed to create directory: $path");
            }
        }
    }

    private function initializeClient(): void
    {
        $this->client = new Client();
        $this->client->setApplicationName('THOMASONS V3 Google Integration');
        $this->client->setScopes([
            Calendar::CALENDAR,          // full calendar access
            Gmail::GMAIL_SEND,           // send emails only (least privilege)
            Drive::DRIVE_FILE,           // access files you create/own (or use DRIVE_READONLY if you only read)
        ]);
        $this->client->setAuthConfig($this->credentialsPath);
        $this->client->setAccessType('offline');   // needed for refresh token
        $this->client->setPrompt('consent');

        // Load & auto-refresh token if it exists
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);

            if ($this->client->isAccessTokenExpired()) {
                if ($refreshToken = $this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
                    $this->log('Google token refreshed automatically', 'INFO');
                }
            }
        }
    }

    /**
     * Returns the raw Google_Client (useful for advanced usage)
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get authorization URL for initial OAuth consent (call from a controller)
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $this->client->setRedirectUri($redirectUri);
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback (call from your /google/callback route)
     * Example: /php/google/callback?code=4/0A...
     */
    public function handleAuthCallback(string $authCode): void
    {
        $this->client->fetchAccessTokenWithAuthCode($authCode);
        file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
        $this->log('Google OAuth token saved successfully', 'INFO');
    }

    /**
     * Quick check if we have a valid token
     */
    public function isAuthenticated(): bool
    {
        $token = $this->client->getAccessToken();
        if (!$token) {
            return false;
        }
        if ($this->client->isAccessTokenExpired()) {
            return (bool) $this->client->getRefreshToken();
        }
        return true;
    }

    /* ================================================================
       SERVICE GETTERS
       ================================================================ */

    public function getCalendarService(): Calendar
    {
        return new Calendar($this->client);
    }

    public function getGmailService(): Gmail
    {
        return new Gmail($this->client);
    }

    public function getDriveService(): Drive
    {
        return new Drive($this->client);
    }

    /* ================================================================
       PUBLIC API METHODS (ready to use in any controller)
       ================================================================ */

    /**
     * Add an event to the user's primary Google Calendar
     * $eventParams example:
     * [
     *   'summary'     => 'Team Meeting',
     *   'description' => 'Discuss Q3 goals',
     *   'start'       => ['dateTime' => '2026-04-15T10:00:00+01:00', 'timeZone' => 'Europe/London'],
     *   'end'         => ['dateTime' => '2026-04-15T11:00:00+01:00', 'timeZone' => 'Europe/London'],
     * ]
     */
    public function createCalendarEvent(array $eventParams): array
    {
        try {
            $calendarService = $this->getCalendarService();
            $event = new \Google\Service\Calendar\Event($eventParams);

            $created = $calendarService->events->insert('primary', $event);

            $this->log('Calendar event created', 'INFO', ['eventId' => $created->getId()]);

            return [
                'success'  => true,
                'eventId'  => $created->getId(),
                'htmlLink' => $created->getHtmlLink() ?? null,
            ];
        } catch (Exception $e) {
            $this->log('Google Calendar error: ' . $e->getMessage(), 'ERROR');
            throw new RuntimeException('Failed to create calendar event: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a plain Gmail (with optional attachments)
     */
    public function sendGmail(string $to, string $subject, string $body, array $attachments = []): string
    {
        try {
            $gmailService = $this->getGmailService();
            $raw = $this->createRawMessage($to, $subject, $body, $attachments);

            $message = new \Google\Service\Gmail\Message();
            $message->setRaw($raw);

            $sent = $gmailService->users_messages->send('me', $message);

            $this->log('Gmail sent', 'INFO', ['messageId' => $sent->getId()]);

            return $sent->getId();
        } catch (Exception $e) {
            $this->log('Gmail send error: ' . $e->getMessage(), 'ERROR');
            throw new RuntimeException('Failed to send Gmail: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Send Gmail AND attach a file that lives in the user's Google Drive
     * (exactly what you asked for in the example)
     */
    public function sendGmailWithDriveAttachment(
        string $to,
        string $subject,
        string $body,
        string $driveFileId
    ): string {
        try {
            $driveService = $this->getDriveService();

            // 1. Get file metadata
            $file = $driveService->files->get($driveFileId, ['fields' => 'name,mimeType']);

            // 2. Download binary content (works with the official client)
            $response = $driveService->files->get($driveFileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            $attachments = [[
                'filename' => $file->getName(),
                'content'  => $content,
                'mimeType' => $file->getMimeType() ?? 'application/octet-stream',
            ]];

            $this->log('Drive file attached to Gmail', 'INFO', [
                'driveFileId' => $driveFileId,
                'filename'    => $file->getName(),
            ]);

            return $this->sendGmail($to, $subject, $body, $attachments);
        } catch (Exception $e) {
            $this->log('Drive → Gmail attachment error: ' . $e->getMessage(), 'ERROR');
            throw new RuntimeException('Failed to send Gmail with Drive attachment: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Private MIME builder (multipart/mixed) used by both send methods
     */
    private function createRawMessage(
        string $to,
        string $subject,
        string $body,
        array $attachments = []
    ): string {
        $boundary = uniqid('boundary_', true);

        $raw = "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

        // Text part
        $raw .= "--{$boundary}\r\n";
        $raw .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
        $raw .= $body . "\r\n\r\n";

        // Attachments
        foreach ($attachments as $att) {
            $filename = $att['filename'] ?? 'file';
            $content  = $att['content'] ?? '';
            $mimeType = $att['mimeType'] ?? 'application/octet-stream';

            $b64 = base64_encode($content);

            $raw .= "--{$boundary}\r\n";
            $raw .= "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n";
            $raw .= "Content-Transfer-Encoding: base64\r\n";
            $raw .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $raw .= chunk_split($b64) . "\r\n";
        }

        $raw .= "--{$boundary}--\r\n";

        // Gmail requires base64url encoding (no padding)
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
