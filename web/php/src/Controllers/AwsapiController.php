<?php

namespace App\Controllers;

/**
 * Standalone AwsapiController
 * Provides a clean mock identity endpoint aligned with framework conventions.
 */
class AwsapiController extends BaseCloudController
{
    /**
     * Entry point: Returns mock AWS STS caller identity data.
     */
    public function index(): void
    {
        $this->json([
            'success'     => true,
            'code'        => 200,
            'error'       => null,
            'environment' => 'test',
            'mock'        => true,
            'data'        => [
                'account'  => '123456789012',
                'arn'      => 'arn:aws:iam::123456789012:user/paul.mcintosh',
                'userName' => 'paul.mcintosh',
                'region'   => 'eu-west-2',
            ],
        ]);
    }
}