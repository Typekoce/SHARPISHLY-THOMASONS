<?php

namespace App\Google;

use App\Models\GoogleTokenModel;
use Google\Client;

class GoogleServiceFactory
{
    private $client;
    private $tokenModel;
    private $whitelist = [
        'Gmail'    => \Google\Service\Gmail::class,
        'Calendar' => \Google\Service\Calendar::class,
        'Drive'    => \Google\Service\Drive::class,
        'Sheets'   => \Google\Service\Sheets::class,
    ];

    public function __construct(GoogleTokenModel $tokenModel)
    {
        $this->tokenModel = $tokenModel;
        $this->client = new Client();
        $this->client->setAuthConfig(getenv('GOOGLE_CREDENTIALS_PATH'));
        $this->client->setAccessType('offline');
    }

    public function create(string $serviceName, int $userId)
    {
        if (!isset($this->whitelist[$serviceName])) throw new \Exception("Service not whitelisted.");

        $tokenData = $this->tokenModel->getToken($userId, 'google');
        if (!$tokenData) throw new \Exception("No valid token found.");

        $this->client->setAccessToken($tokenData['access_token']);

        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
            $newToken = $this->client->getAccessToken();
            $this->tokenModel->saveToken($userId, 'google', [
                'access_token' => $newToken['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'scopes' => $tokenData['scopes'],
                'expires_at' => date('Y-m-d H:i:s', time() + $newToken['expires_in'])
            ]);
        }

        $class = $this->whitelist[$serviceName];
        return new $class($this->client);
    }
}