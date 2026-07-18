<?php

namespace App\Controllers;

/**
 * Standalone GoogleapiController
 * Handles OAuth2 flow and endpoint interaction within a single class.
 */
class GoogleapiController extends BaseController
{
    /**
     * Entry point: Fetches user profile data.
     */
    public function index()
    {
        $token = $this->getLocalToken();
        
        if (!$token) {
            return $this->jsonResponse(['error' => 'no_token', 'message' => 'No access token available'], 401);
        }

        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $data = $this->curlRequest($url, ['Authorization: Bearer ' . $token]);

        return $data ? $this->jsonResponse($data) : $this->jsonResponse(['error' => 'userinfo_failed'], 502);
    }

    /**
     * OAuth2 Code Exchange.
     * Trigger: /google-auth/callback?code=...
     */
    public function callback()
    {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            return $this->jsonResponse(['error' => 'missing_code'], 400);
        }

        // Configuration sourced from constants defined in bootstrap via env.php[cite: 2, 4]
        $params = [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ];

        $response = $this->curlRequest('https://oauth2.googleapis.com/token', [], $params);
        
        if (!is_array($response) || empty($response['access_token'])) {
            return $this->jsonResponse(['error' => 'token_exchange_failed', 'details' => $response], 502);
        }

        $this->saveLocalToken($response['access_token']);
        return $this->jsonResponse(['status' => 'token_saved']);
    }

    /**
     * Executes raw cURL request (GET/POST).
     */
    private function curlRequest(string $url, array $headers = [], array $postData = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        if ($errno !== 0 || $raw === false) return null;
        
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function getLocalToken(): ?string
    {
        $path = $this->loc->storage('google/tokens/access_token.txt');
        return is_file($path) ? trim(file_get_contents($path)) : null;
    }

    private function saveLocalToken(string $token): void
    {
        $path = $this->loc->storage('google/tokens/access_token.txt');
        @mkdir(dirname($path), 0700, true);
        file_put_contents($path, $token);
    }

    private function jsonResponse(array $data, int $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        return null;
    }
}