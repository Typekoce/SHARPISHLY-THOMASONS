<?php

namespace App\Controllers;

use App\Models\FacebookModel;

class FacebookController extends BaseController{

    // In FacebookController.php
    public function get() {
        $token = 'YOUR_ACTUAL_TOKEN_HERE';
        $facebook = new FacebookModel();
        $res = $facebook->request("https://graph.facebook.com/v20.0/me?fields=id,name", $token);
        $this->json($res); // This ensures you see the response in your browser/console
    }

    public function post() {
        // 1. Get the Page Token
        $accounts = FacebookModel::request('me/accounts', $this->token);
        $page = $accounts['data'][0] ?? null;

        if (!$page) return;

        // 2. Post to the Page
        $res = FacebookModel::request($page['id'] . '/feed', $page['access_token'], [
            'message' => 'Clean MVC implementation'
        ], true);

        $this->json($res);
    }

    public function callback() {
        if (!isset($_GET['code'])) return;

        $auth = FacebookModel::auth($_GET['code']);
        
        // In a real app, pass $auth['access_token'] to a TokenModel to save in MariaDB
        echo "New Token: " . ($auth['access_token'] ?? 'Failed');
    }
}