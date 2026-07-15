<?php

namespace App\Controllers;

use RuntimeException;

//TODO: Might store commands in DB

class TerminalController extends BaseController {

    public function load(string $command): void {

        $run = $this->alias($command);

        if($run != false){


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
            if (file_put_contents($tempFile, "#!/bin/bash\n" . $run, LOCK_EX) === false) {

                throw new RuntimeException("Failed to write to temp file: $tempFile");
            }
            
            // 2. Set as executable
            chmod($tempFile, 0755);

            // 3. Atomically rename
            if (!rename($tempFile, $finalFile)) {

                throw new RuntimeException("Failed to rename $tempFile to $finalFile");
            }


        }
        


    }

    public function alias($command){

        $terminal = array(
            'ls' => 'ls -all',
            'history' => '',
        );

        if(isset($terminal[$command])){

            return $terminal[$command];

        }

        return false;

    }

}