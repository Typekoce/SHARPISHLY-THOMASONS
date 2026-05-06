<?php

namespace App\Controllers;

class FacebookController {
    private $token = 'USER_TOKEN_FROM_DATABASE';

    public function get() {
        $res = FacebookModel::request('me', $this->token, ['fields' => 'id,name']);
        echo json_encode($res);
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

        echo json_encode($res);
    }

    public function callback() {
        if (!isset($_GET['code'])) return;

        $auth = FacebookModel::auth($_GET['code']);
        
        // In a real app, pass $auth['access_token'] to a TokenModel to save in MariaDB
        echo "New Token: " . ($auth['access_token'] ?? 'Failed');
    }
}