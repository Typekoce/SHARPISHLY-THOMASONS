<?php

namespace App\Google;

/**
 * Custom Google Service Gateway
 * Orchestrates token lifecycle and service injection for the Sharpishly framework.
 */
class Client
{
    private array $config = [];
    private $tokenModel;

    public function __construct()
    {
        $this->setApplicationName('Sharpishly');
        $this->setAuthConfig(getenv('GOOGLE_CREDENTIALS_PATH'));
        $this->setAccessType('offline');
        
        // Inject token model for lifecycle management
        $this->tokenModel = new \App\Models\GoogleTokenModel();
    }

    public function setApplicationName(string $name): void { $this->config['app_name'] = $name; }
    public function setAuthConfig(string $path): void { $this->config['auth_path'] = $path; }
    public function setAccessType(string $type): void { $this->config['access_type'] = $type; }

    /**
     * Factory method to return a specific service instance.
     * This replaces the need for the third-party Google SDK object.
     */
    public function getService(string $serviceName, int $userId)
    {
        $tokenData = $this->tokenModel->getToken($userId, 'google');
        
        // Corrected: Removed the extra backslash
        $className = "\\App\\Google\\Services\\" . $serviceName;
        return new $className($tokenData, $this->config);
    }

    public function getClient(){
        return $this;
    }

    public function getAccessToken(){
        // TODO: get token from db, etc
        return 'ACCESS_TOKEN';
    }
}