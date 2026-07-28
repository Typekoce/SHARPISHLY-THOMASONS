<?php

$host = 'http://localhost';

$requiredHeaders = [
    'x-content-type-options'  => 'nosniff',
    'x-frame-options'         => 'SAMEORIGIN',
    'referrer-policy'         => 'strict-origin-when-cross-origin',
    'content-security-policy' => "default-src 'self'",
];

// 1. Check HTTP Redirection
$ch = curl_init($host);
curl_setopt_array($ch, [
    CURLOPT_NOBODY         => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_TIMEOUT        => 5
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "[+] Target Host: {$host}\n";
echo "[+] HTTP Response Code: {$httpCode}\n";

if ($httpCode === 301 || $httpCode === 302) {
    echo "[PASS] HTTP redirection is active.\n";
} else {
    echo "[WARN] HTTP is not redirecting to HTTPS automatically (Code: {$httpCode}).\n";
}

// 2. Parse & Validate Response Headers
$headers = [];
$lines = explode("\r\n", (string)$response);

foreach ($lines as $line) {
    if (strpos($line, ':') !== false) {
        list($key, $value) = explode(':', $line, 2);
        $headers[strtolower(trim($key))] = trim($value);
    }
}

echo "\n--- Header Audit ---\n";
foreach ($requiredHeaders as $header => $expectedValue) {
    if (isset($headers[$header])) {
        echo "[PASS] {$header}: {$headers[$header]}\n";
    } else {
        echo "[FAIL] Missing required header: {$header}\n";
    }
}