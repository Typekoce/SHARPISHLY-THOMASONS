<?php

namespace App\Services;

/**
 * EnvironmentService
 * * Responsible for detecting the current execution context.
 * Uses values defined in the root .env file and mapped via docker-compose.
 */
class EnvironmentService extends BaseService {

    /**
     * Determines if the application is in Development mode.
     * Checked via APP_DEV (boolean-like) or APP_ENV.
     * * @return bool
     */
    public function isDevMode(): bool {
        $appDev = getenv('APP_DEV');
        $appEnv = getenv('APP_ENV');

        // Check if explicitly set to dev or if environment is 'local'/'development'
        return ($appDev === 'true' || $appDev === '1' || $appEnv === 'development' || $appEnv === 'local');
    }

    /**
     * Determines if the application is in Production mode.
     * * @return bool
     */
    public function isProductionMode(): bool {
        return getenv('APP_ENV') === 'production';
    }

    /**
     * Helper to get a specific environment variable with a default fallback.
     * * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null) {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }
}