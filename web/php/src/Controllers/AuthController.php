<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuthModel;

class AuthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new AuthModel();
    }

    public function google(): void
    {
        $this->handleOAuthFlow('google');
    }

    public function github(): void
    {
        $this->handleOAuthFlow('github');
    }

    /**
     * Unified OAuth execution handler for Google and GitHub providers.
     */
    private function handleOAuthFlow(string $provider): void
    {
        $error = $this->request('error');
        if (!empty($error)) {
            $this->redirectWithError((string) $error);
        }

        $code  = $this->request('code');
        $state = $this->request('state');

        if (empty($code)) {
            $this->redirectToProvider($provider);
        }

        // Validate state parameter against session CSRF token
        if (empty($state) || $state !== ($this->session->get('oauth_state'))) {
            $this->redirectWithError('invalid_state');
        }
        $this->session->remove('oauth_state');

        $profile = ($provider === 'google') 
            ? $this->processGoogleAuth((string) $code) 
            : $this->processGithubAuth((string) $code);

        if (empty($profile['provider_uid'])) {
            $this->redirectWithError('authentication_failed');
        }

        $user = $this->model->findOrCreateOuser($profile);

        // Populate session state
        $this->session->set('user_id', $user['id'] ?? null);
        $this->session->set('provider', $provider);
        $this->session->set('auth_email', $user['email'] ?? null);

        // Redirect to authenticated home route
        header('Location: /');
        exit;
    }

    /**
     * Constructs authorization URL and redirects client.
     */
    private function redirectToProvider(string $provider): void
    {
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);

        $appUrl = $_ENV['APP_URL'] ?? 'http://127.0.0.1';

        if ($provider === 'google') {
            $clientId    = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
            $redirectUri = $appUrl . '/php/auth-google-callback';
            $scope       = urlencode('email profile');

            $url = "https://accounts.google.com/o/oauth2/v2/auth"
                . "?client_id={$clientId}"
                . "&redirect_uri=" . urlencode($redirectUri)
                . "&response_type=code"
                . "&scope={$scope}"
                . "&state={$state}"
                . "&access_type=offline"
                . "&prompt=select_account";
        } else {
            $clientId    = $_ENV['GITHUB_CLIENT_ID'] ?? '';
            $redirectUri = $appUrl . '/php/auth/github';
            $scope       = urlencode('user:email');

            $url = "https://github.com/login/oauth/authorize"
                . "?client_id={$clientId}"
                . "&redirect_uri=" . urlencode($redirectUri)
                . "&scope={$scope}"
                . "&state={$state}";
        }

        header("Location: {$url}");
        exit;
    }

    /**
     * Google Token Exchange & Profile Fetching via Orm
     */
    private function processGoogleAuth(string $code): array
    {
        $redirectUri = ($_ENV['APP_URL'] ?? 'http://127.0.0.1') . '/php/auth-google-callback';

        $tokenResponse = $this->orm->execute([
            'source' => 'Ollama', // Reuses Orm execution for standard HTTP endpoint POST calls
            'endpoint' => 'https://oauth2.googleapis.com/token',
            'method' => 'POST',
            'data'   => [
                'client_id'     => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUri,
            ]
        ]);

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (!$accessToken) {
            return [];
        }

        $userProfile = $this->orm->execute([
            'source' => 'Ollama',
            'endpoint' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'method' => 'GET',
            'token'  => $accessToken,
        ]);

        return [
            'provider'      => 'google',
            'provider_uid'  => (string) ($userProfile['id'] ?? ''),
            'email'         => $userProfile['email'] ?? null,
            'name'          => $userProfile['name'] ?? null,
            'access_token'  => $accessToken,
            'refresh_token' => $tokenResponse['refresh_token'] ?? null,
            'expires_at'    => empty($tokenResponse['expires_in'])
                ? null
                : date('Y-m-d H:i:s', time() + (int) $tokenResponse['expires_in']),
        ];
    }

    /**
     * GitHub Token Exchange & Profile Fetching via Orm
     */
    private function processGithubAuth(string $code): array
    {
        $redirectUri = ($_ENV['APP_URL'] ?? 'http://127.0.0.1') . '/php/auth/github';

        $tokenResponse = $this->orm->execute([
            'source' => 'Ollama',
            'endpoint' => 'https://github.com/login/oauth/access_token',
            'method' => 'POST',
            'headers' => ['Accept: application/json'],
            'data'   => [
                'client_id'     => $_ENV['GITHUB_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['GITHUB_CLIENT_SECRET'] ?? '',
                'code'          => $code,
                'redirect_uri'  => $redirectUri,
            ]
        ]);

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (!$accessToken) {
            return [];
        }

        $githubHeaders = [
            'Accept: application/json',
            'User-Agent: Sharpishly-App'
        ];

        $userProfile = $this->orm->execute([
            'source'  => 'Ollama',
            'endpoint' => 'https://api.github.com/user',
            'method'  => 'GET',
            'token'   => $accessToken,
            'headers' => $githubHeaders,
        ]);

        $email = $userProfile['email'] ?? null;

        if (!$email) {
            $emailsList = $this->orm->execute([
                'source'  => 'Ollama',
                'endpoint' => 'https://api.github.com/user/emails',
                'method'  => 'GET',
                'token'   => $accessToken,
                'headers' => $githubHeaders,
            ]);

            if (is_array($emailsList)) {
                foreach ($emailsList as $e) {
                    if (!empty($e['primary']) && !empty($e['verified'])) {
                        $email = $e['email'];
                        break;
                    }
                }
            }
        }

        return [
            'provider'      => 'github',
            'provider_uid'  => (string) ($userProfile['id'] ?? ''),
            'email'         => $email,
            'name'          => $userProfile['name'] ?? ($userProfile['login'] ?? null),
            'access_token'  => $accessToken,
            'refresh_token' => null,
            'expires_at'    => null,
        ];
    }

    /**
     * Standardized error redirect helper.
     */
    private function redirectWithError(string $error): void
    {
        $this->logger->error("Auth Failure: {$error}");
        header('Location: /login?error=' . urlencode($error));
        exit;
    }
}