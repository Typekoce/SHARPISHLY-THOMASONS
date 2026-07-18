<?php

namespace App\Controllers;

/**
 * Standalone AzureapiController
 * Handles Microsoft Graph/Azure AD authentication and resource consumption.
 */
class AzureapiController extends BaseController
{
    /**
     * Entry point: Fetches Azure tenant/resource information.
     */
    public function index()
    {
        $token = $this->getLocalToken();
        
        if (!$token) {
            return $this->jsonResponse(['error' => 'no_token', 'message' => 'Azure access token missing'], 401);
        }

        // Example endpoint: List resources in the tenant
        $url = 'https://graph.microsoft.com/v1.0/me';
        $data = $this->curlRequest($url, ['Authorization: Bearer ' . $token]);

        return $data ? $this->jsonResponse($data) : $this->jsonResponse(['error' => 'azure_api_failed'], 502);
    }

    /**
     * OAuth2 Code Exchange for Azure AD.
     * Trigger: /azure-auth/callback?code=...
     */
    public function callback()
    {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            return $this->jsonResponse(['error' => 'missing_code'], 400);
        }

        // Azure-specific configuration
        $params = [
            'code'          => $code,
            'client_id'     => AZURE_CLIENT_ID,
            'client_secret' => AZURE_CLIENT_SECRET,
            'redirect_uri'  => AZURE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
            'scope'         => 'https://graph.microsoft.com/.default'
        ];

        // Use the tenant-specific or common token endpoint
        $url = 'https://login.microsoftonline.com/' . AZURE_TENANT_ID . '/oauth2/v2.0/token';
        $response = $this->curlRequest($url, [], $params);
        
        if (!is_array($response) || empty($response['access_token'])) {
            return $this->jsonResponse(['error' => 'azure_token_failed', 'details' => $response], 502);
        }

        $this->saveLocalToken($response['access_token']);
        return $this->jsonResponse(['status' => 'azure_token_saved']);
    }

    /**
     * Helper to build authorization URL for the frontend.
     */
    public function getAuthorizeUrl()
    {
        $params = [
            'client_id'     => AZURE_CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri'  => AZURE_REDIRECT_URI,
            'scope'         => 'https://graph.microsoft.com/.default',
            'state'         => bin2hex(random_bytes(16))
        ];

        return 'https://login.microsoftonline.com/' . AZURE_TENANT_ID . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    private function curlRequest(string $url, array $headers = [], array $postData = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5
        ]);
        
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
        $path = $this->loc->storage('azure/tokens/access_token.txt');
        return is_file($path) ? trim(file_get_contents($path)) : null;
    }

    private function saveLocalToken(string $token): void
    {
        $path = $this->loc->storage('azure/tokens/access_token.txt');
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