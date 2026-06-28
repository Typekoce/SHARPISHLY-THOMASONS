<?php

namespace App\Controllers;

use App\Google\Client;

class GoogleController extends BaseController
{
    public function auth()
    {
        $client = new Client();
        
        // Return status for your health monitor
        return $this->json([
            'status' => 'success',
            'connected' => $client->isConnected(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}