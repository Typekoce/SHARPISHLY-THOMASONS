<?php

namespace App\Controllers;

use App\Google\Client;

class GoogleController extends BaseController
{
    public function auth()
    {
        $wrapper = new Client();
        $client = $wrapper->getClient();
        
        return $this->json([
            'status'    => 'success',
            'connected' => !empty($client->getAccessToken()),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}