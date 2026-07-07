<?php

namespace App\Security;

/**
 * Base class for all security modules
 */
class BaseSecurity {
    protected $config;

    public function __construct(array $config = []) {
        $this->config = $config;
    }
}