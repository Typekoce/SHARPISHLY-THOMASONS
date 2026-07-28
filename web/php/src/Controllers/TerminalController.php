<?php

namespace App\Controllers;

use RuntimeException;
use App\Models\TerminalModel;

//TODO: Might store commands in DB

/**
 * The supervisor_worker.sh watches the cmd folders
 */
class TerminalController extends BaseController {

    public $tbl = 'terminal';

    public function load(string $command): void {

        $terminal = new TerminalModel();

        $run = $terminal->alias($command);

        if($run != false){


            $dir = $this->loc->storage("cmd/jobs/waiting");
            
            // Ensure the directory exists
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $filename = "job_" . time() . "_" . uniqid() . ".sh";

            $tempFile = $dir . "/temp_" . uniqid();

            $finalFile = $dir . "/" . $filename;

            $save = array(
                'filename'      => $filename,
                'command'       => $command,
                'created_at'    => $this->now()
            );

            $result = $this->db->save($this->tbl, $save);

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

}