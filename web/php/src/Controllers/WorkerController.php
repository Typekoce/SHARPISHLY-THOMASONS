<?php

namespace App\Controllers;

use RuntimeException;

class WorkerController extends BaseController {

    public function dispatch(string $command): void {

        // http://localhost/php/worker/dispatch/worker?query="ls"


        if(isset($_GET['query'])){
            $command = urldecode($_GET['query']);
        }

        $dir = $this->loc->storage("cmd/jobs/waiting");
        
        // Ensure the directory exists
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = "job_" . time() . "_" . uniqid() . ".sh";
        $tempFile = $dir . "/temp_" . uniqid();
        $finalFile = $dir . "/" . $filename;

        // 1. Write content
        // Use LOCK_EX to ensure the write is clean
        if (file_put_contents($tempFile, "#!/bin/bash\n" . $command, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write to temp file: $tempFile");
        }
        
        // 2. Set as executable
        chmod($tempFile, 0755);

        // 3. Atomically rename
        if (!rename($tempFile, $finalFile)) {
            throw new RuntimeException("Failed to rename $tempFile to $finalFile");
        }
    }

    public function command($cmd = ''){

        $cmd="history | grep 'update'";

        $url = $this->url($cmd);

        $this->request($url);

    }

    public function url($url = ''){

        http://localhost/php/worker/dispatch/worker?query="ls"

        return "http://" . $_SERVER['HTTP_HOST'] . "/php/worker/dispatch/worker?query='" . urlencode($url) . "'";

    }

}