<?php

namespace App\Controllers;

use RuntimeException;

//TODO: Might store commands in DB

/**
 * The supervisor_worker.sh watches the cmd folders
 */
class TerminalController extends BaseController {

    public $tbl = 'terminal';

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

    public function alias($command)
    {
        $terminal = array(
            'ls'             => 'ls -all',
            'history'        => 'history',
            'history-update' => "history | grep 'update'",
            
            // Project maintenance & logs
            'logs-app'       => 'tail -f storage/logs/app.log',
            // Updated to reflect the specific log path for the project
            'logs-nginx'     => 'tail -f /var/log/nginx/sharpishly_access.log',
            'db-migrate'     => 'curl -i http://localhost/php/scaffold/migrate',
            
            // Safety-first cleanup: targeting job-specific files
            'cleanup'        => 'find storage/cmd/jobs/waiting -type f -name "job_*" -delete',
            
            // Deterministic service check
            'status'         => 'sh -lc "systemctl status mariadb; systemctl status nginx"',
            
            // Schema validation
            'schema-check'   => 'php scripts/schema-check.php',

            // test docker on @maxie
            'docker-maxie' => 'ssh -o ConnectTimeout=5 maxie "docker ps --format \'table {{.ID}}\t{{.Image}}\t{{.Status}}\t{{.Names}}\'"',
            
            //
            'docker-maxie-status'   => 'echo "YOUR_PASSWORD" | ssh -t maxie "sudo -S systemctl status docker"',
            
            'gmail-inbox'   => 'himalaya envelope list',

            // Git log
            'git-log'       => 'git log --oneline --graph --decorate',

            // Check which files render json endpoints
            'json-endpoints' => "grep -R '$this->json(' web/php/src/Controllers/"
        );

        if (isset($terminal[$command])) {
            return $terminal[$command];
        }

        return false;
    }

}