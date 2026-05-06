<?php

namespace App\Models;

class FacebookModel {
    private static $base = 'https://graph.facebook.com/v20.0/';

    public static function request($endpoint, $token, $params = [], $post = false) {
        $url = self::$base . $endpoint;
        if (!$post) $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        }

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        return json_decode(curl_exec($ch), true);
    }

    public static function auth($code) {
        // OAuth uses the same base but different parameter handling (no Bearer header)
        return self::request('oauth/access_token', null, [
            'client_id' => 'YOUR_APP_ID',
            'client_secret' => 'YOUR_APP_SECRET',
            'redirect_uri' => 'YOUR_REDIRECT_URL',
            'code' => $code
        ]);
    }
}