<?php

namespace App\Models;

class FacebookModel extends BaseModel {

    // In FacebookModel.php
    public function fetchMe() {
        return $this->request("https://graph.facebook.com/v20.0/me", "YOUR_TOKEN");
    }



    /**
     * Example: Fetching user info and saving a log to MariaDB
     */
    public function getUser(string $token): array
    {
        $response = $this->http("https://graph.facebook.com/v20.0/me?fields=id,name", $token);

        if ($response['success']) {
            // Use $this->db to store the interaction (No raw SQL permitted)
            $this->db->insert('api_logs', [
                'provider' => 'facebook',
                'identifier' => $response['data']['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $response;
    }

    /**
     * Dedicated OAuth Exchange
     */
    public function exchangeCode(string $code): array
    {
        return $this->http("https://graph.facebook.com/v20.0/oauth/access_token", null, [
            'client_id' => 'ID',
            'client_secret' => 'SECRET',
            'redirect_uri' => 'URL',
            'code' => $code
        ], 'POST', false); // OAuth uses form-urlencoded
    }
}