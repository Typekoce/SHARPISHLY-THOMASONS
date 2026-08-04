<?php

namespace App\Models;

class TerminalModel extends BaseModel {


    public function alias($command)
    {
        $terminal = array(
            'ls'             => 'ls -all',
            'history'        => 'history',
            'history-update' => "history | grep 'update'",
            
            // Project maintenance & logs
            'logs-app'       => 'tail -f storage/log/*.* storage/logs/*.*',
            // Updated to reflect the specific log path for the project
            'logs-nginx'     => 'tail -f /var/log/nginx/sharpishly_access.log',
            'db-migrate'     => 'curl -i http://localhost/php/scaffold/migrate',
            
            // Safety-first cleanup: targeting job-specific files
            'cleanup'          => 'find storage/cmd/jobs/waiting -type f -name "job_*" -delete',
            'cleanup-jobs'     => 'find storage/cmd/jobs/waiting -type f -name "job_*" -delete',
            'cleanup-jobs-all' => 'find storage/cmd/jobs/{waiting,processing,completed} -type f -name "job_*" -delete',
            
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

            // Fixed: Escaped variable dollar sign inside double-quoted string
            'json-endpoints' => "grep -R '\$this->json(' web/php/src/Controllers/",
            // Find Api Controllers
            'api-controllers'   => "find web/php/src/Controllers/ -type f -iname '*apiController*' -exec grep -Hni 'json' {} +",

            // NMAP
            'nmap-local' => 'nmap 192.168.0.218 -sV -T4 2>&1',

            'nginx-header-check' => 'php diagnostics/nginx-header-check.php',

            // SSH Key Management & GitHub Verification on @maxie
            'maxie-keygen'        => 'ssh maxie@192.168.0.90 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && [ -f ~/.ssh/id_ed25519 ] || ssh-keygen -t ed25519 -C \"maxie@192.168.0.90\" -N \"\" -f ~/.ssh/id_ed25519"',
            'maxie-get-key'       => 'ssh maxie@192.168.0.90 "cat ~/.ssh/id_ed25519.pub"',
            'maxie-verify-github'  => 'ssh -o BatchMode=yes maxie@192.168.0.90 "ssh -T -o StrictHostKeyChecking=accept-new git@github.com"',

            // deploy Sharpishly repo to maxie@192.168.0.90
            'maxie-deploy-sharpishly'   => 'ssh -o BatchMode=yes maxie@192.168.0.90 "rm -rf sharpishly && git clone git@github.com:Typekoce/SHARPISHLY-THOMASONS.git sharpishly && cd sharpishly && chmod +x final_installation.sh && ./final_installation.sh"',

            // Deployment support aliases
            'maxie-setup-sudoers' => 'ssh -t maxie@192.168.0.90 "echo \"maxie ALL=(ALL) NOPASSWD: ALL\" | sudo tee /etc/sudoers.d/maxie > /dev/null && sudo chmod 0440 /etc/sudoers.d/maxie"',
            'maxie-prep-ollama'   => 'ssh -o BatchMode=yes maxie@192.168.0.90 "ollama pull llama3 && ollama pull jina/jina-embeddings-v2-small-en"',
            'maxie-health-check'  => 'ssh -o BatchMode=yes maxie@192.168.0.90 "db-check && sudo systemctl status nginx"'
        );

        if (isset($terminal[$command])) {
            return $terminal[$command];
        }

        return false;
    }


}