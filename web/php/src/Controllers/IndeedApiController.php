<?php

namespace App\Controllers;

class IndeedApiController extends BaseController
{
    /**
     * Entry point: Returns internal job application tracking data.
     * Route: /indeed-api?q=Software+Developer
     */
    public function index(): void
    {
        $query    = trim($this->request('q') ?? 'Software Developer');
        $location = trim($this->request('location') ?? 'United Kingdom');

        $hasToken = $this->getLocalToken() !== null;

        // Structured payload mapped to the #agentic frontend contract
        $jobs = [
            [
                'id'           => 1,
                'role'         => 'Senior Software Engineer',
                'company'      => 'TechCorp Global',
                'platform'     => 'Indeed',
                'summary'      => 'CV tailored to emphasize zero-dependency MVC architecture, custom shell scripts, and raw API integrations.',
                'status'       => 'applied',
                'status_label' => 'Applied',
                'applied_at'   => '2026-10-24',
                'has_cv'       => true,
            ],
            [
                'id'           => 2,
                'role'         => 'Backend Systems Architect',
                'company'      => 'DataFlow Ltd',
                'platform'     => 'Indeed',
                'summary'      => 'Agent is currently polling the endpoint to finalize the resume mapping.',
                'status'       => 'pending',
                'status_label' => 'Generating CV',
                'applied_at'   => 'Pending API Response...',
                'has_cv'       => false,
            ],
            [
                'id'           => 3,
                'role'         => 'Systems Developer (PHP/C++)',
                'company'      => 'Innovate Solutions',
                'platform'     => 'Indeed',
                'summary'      => 'CV tailored to highlight containerization without Docker and standalone state machines.',
                'status'       => 'applied',
                'status_label' => 'Applied',
                'applied_at'   => '2026-10-23',
                'has_cv'       => true,
            ],
        ];

        $this->json([
            'success'      => true,
            'token_active' => $hasToken,
            'query'        => [
                'q'        => $query,
                'location' => $location,
            ],
            'results'      => $jobs,
        ]);
    }

    /**
     * Client credentials OAuth flow stub for Indeed Partner API integration.
     * Route: /indeed-token
     */
    public function fetchToken(): void
    {
        if (!defined('INDEED_CLIENT_ID') || !defined('INDEED_CLIENT_SECRET')) {
            $this->json([
                'success' => false,
                'error'   => 'missing_credentials',
                'message' => 'INDEED_CLIENT_ID and INDEED_CLIENT_SECRET constants are not defined.',
            ], 400);
            return;
        }

        $url = 'https://apis.indeed.com/oauth/v2/tokens';

        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Sharpishly-Agent/1.0',
        ];

        $params = [
            'client_id'     => INDEED_CLIENT_ID,
            'client_secret' => INDEED_CLIENT_SECRET,
            'grant_type'    => 'client_credentials',
            'scope'         => 'employer_access',
        ];

        $response = $this->curlRequestForm($url, $headers, $params);

        if (!$response['ok'] || empty($response['data']['access_token'])) {
            $this->json([
                'success'         => false,
                'error'           => 'token_exchange_failed',
                'indeed_feedback' => $response['data'] ?? null,
            ], 502);
            return;
        }

        $this->saveLocalToken($response['data']['access_token']);

        $this->json([
            'success'    => true,
            'status'     => 'token_saved',
            'expires_in' => $response['data']['expires_in'] ?? null,
        ]);
    }

    /**
     * Executes a form-encoded cURL request.
     */
    private function curlRequestForm(string $url, array $headers, array $params): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Sharpishly-Agent/1.0',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            return ['ok' => false, 'status' => 0, 'error' => $error];
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'status' => $status, 'error' => 'invalid_json', 'raw' => $raw];
        }

        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $data];
    }

    private function getLocalToken(): ?string
    {
        $path = $this->loc->storage('indeed/tokens/access_token.txt');
        return is_file($path) ? trim(file_get_contents($path)) : null;
    }

    private function saveLocalToken(string $token): void
    {
        $path = $this->loc->storage('indeed/tokens/access_token.txt');
        @mkdir(dirname($path), 0700, true);
        file_put_contents($path, $token);
        chmod($path, 0600);
    }
}