<?php
// e.g. App\Controllers\UploadController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Registry;
use App\Services\Db;

class UploadController
{
    public function index(): string
    {
        $file = $_FILES['csv_data'] ?? $_FILES['data_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return json_encode([
                'status'  => 'error',
                'message' => 'No valid file received or upload error: ' . ($file['error'] ?? 'unknown')
            ]);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['csv', 'txt'];
        if (!in_array($ext, $allowed, true)) {
            http_response_code(400);
            return json_encode(['status' => 'error', 'message' => 'Only .csv and .txt files allowed']);
        }

        $newName   = bin2hex(random_bytes(12)) . '.' . $ext;
        $uploadDir = APP_ROOT . 'storage/uploads/';
        $target    = $uploadDir . $newName;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            http_response_code(500);
            return json_encode(['status' => 'error', 'message' => 'Cannot create upload directory']);
        }

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            http_response_code(500);
            return json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']);
        }

        $db = Registry::get(Db::class);

        $payload = json_encode([
            'path'         => $target,
            'original_name'=> $file['name'],
            'size'         => $file['size'],
            'uploaded_at'  => date('c'),
        ], JSON_THROW_ON_ERROR);

        $stmt = $db->prepare("
            INSERT INTO jobs (type, payload, status)
            VALUES (:type, :payload, 'pending')
        ");
        $stmt->execute([
            'type'    => 'csv_ingest',
            'payload' => $payload,
        ]);

        $jobId = $db->lastInsertId();

        return json_encode([
            'status'  => 'accepted',
            'job_id'  => $jobId,
            'message' => 'File queued for background processing',
        ]);
    }
}