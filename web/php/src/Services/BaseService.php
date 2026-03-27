<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Location;

class BaseService {
    public $uploadPath;
    public $location;
    private string $logFile;


    public function __construct() {

        $this->location = New Location();
        $this->uploadPath = $location->storage('uploads');
        
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0775, true);
        }
        
        // Ensure the directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException("Logger cannot create directory: $dir");
            }
        }
    }

}