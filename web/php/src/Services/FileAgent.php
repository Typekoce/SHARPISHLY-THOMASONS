<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Registry;
use RuntimeException;

class FileAgent {
    private string $uploadPath;

    public function __construct() {
        $location = Registry::get(Location::class);
        $this->uploadPath = $location->storage('uploads');
        
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0775, true);
        }
    }

    public function receive(array $fileData): string {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload failed with error code: " . $fileData['error']);
        }

        $safeName = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $fileData['name']);
        $target = $this->uploadPath . DIRECTORY_SEPARATOR . time() . "_" . $safeName;

        if (!move_uploaded_file($fileData['tmp_name'], $target)) {
            throw new RuntimeException("Failed to move uploaded file to storage.");
        }

        return $target;
    }
}