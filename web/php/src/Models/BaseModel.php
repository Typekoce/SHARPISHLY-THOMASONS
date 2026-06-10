<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;
use App\Services\Logger;

class BaseModel {

    protected Db $db;

    public function __construct()
    {
        $logger = $GLOBALS['logger'] ?? new Logger();
        $config = get_env(); 
        $this->db = new Db($config, $logger);
    }

    // In BaseModel.php (The "Pipe")
    public function request(string $url, string $token): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    /**
     * Unified HTTP Execution for Social APIs
     */
    protected function http(string $url, ?string $token, array $params = [], string $method = 'GET', bool $isJson = true): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [];
        if ($token) $headers[] = "Authorization: Bearer $token";
        if ($isJson && $method === 'POST') $headers[] = "Content-Type: application/json";
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $isJson ? json_encode($params) : http_build_query($params));
        }

        $raw = curl_exec($ch);
        $res = json_decode($raw, true) ?? [];
        curl_close($ch);

        // Normalized response for Controllers
        return [
            'success' => !isset($res['error']) && !isset($res['error_code']),
            'data' => $res
        ];
    }
}