<?php
namespace App\Controllers;

use App\Services\MailService;

class EmailController extends BaseController
{
    protected MailService $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    // Generic send endpoint: expects JSON body {to, subject, body, provider?}
    public function send()
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $to = $payload['to'] ?? null;
        $subject = $payload['subject'] ?? 'No subject';
        $body = $payload['body'] ?? '';
        $provider = $payload['provider'] ?? null; // optional

        if (!$to) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing "to"']);
            return;
        }

        try {
            $this->mailService->send($to, $subject, $body, $provider);
            echo json_encode(['status' => 'ok']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Fetch messages for a named provider (query params: provider, folder, limit)
    public function fetch()
    {
        $provider = $_GET['provider'] ?? 'gmail';
        $folder = $_GET['folder'] ?? 'INBOX';
        $limit = intval($_GET['limit'] ?? 25);

        try {
            $messages = $this->mailService->fetch($provider, $folder, $limit);
            echo json_encode(['messages' => $messages]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
