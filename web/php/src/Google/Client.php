<?php

namespace App\Google;

/**
 * Google Client Service
 * Manages OAuth2 authentication and API connectivity.
 */
class Client
{
    private $client;

    public function __construct()
    {
        // Initialize the Google Client
        // Note: We use environmental variables to load paths, 

        $this->client = new \Google\Client();
        $this->client->setApplicationName('Sharpishly');
        $this->client->setAuthConfig(getenv('GOOGLE_CREDENTIALS_PATH'));
        $this->client->addScope(\Google\Service\Calendar::CALENDAR_READONLY);
        $this->client->setAccessType('offline');
    }

    public function isConnected(): bool
    {
        // Logic to verify current token status
        return !empty($this->client->getAccessToken());
    }

    public function getService()
    {
        return new \Google\Service\Calendar($this->client);
    }
}