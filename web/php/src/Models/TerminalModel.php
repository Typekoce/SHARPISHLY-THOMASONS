<?php

namespace App\Models;

class TerminalModel extends BaseModel {

    public function alias($command)
    {
        $terminal = array(
            'ls'             => 'ls -all',
            'history'        => 'history',
            'history-update' => "history | grep 'update'",
            
            // Project maintenance & logs (resilient log tailing)
            'logs-app'       => 'find storage -type f -name "*.log" | xargs tail -f 2>/dev/null',
            'logs-nginx'     => 'tail -f /var/log/nginx/sharpishly_access.log',
            'db-migrate'     => 'curl -i http://localhost/php/scaffold/migrate',
            
            // Safety-first cleanup: targeting job-specific files
            'cleanup'          => 'find storage/cmd/jobs/waiting -type f -name "job_*" -delete',
            'cleanup-jobs'     => 'find storage/cmd/jobs/waiting -type f -name "job_*" -delete',
            'cleanup-jobs-all' => 'bash -c "find storage/cmd/jobs/{waiting,processing,completed} -type f -name \"job_*\" -delete"',
            
            // Deterministic service check
            'status'         => 'sh -lc "systemctl status mariadb; systemctl status nginx"',
            
            // Schema validation
            'schema-check'   => 'php scripts/schema-check.php',

            // Host-level SSH Key Authorization & Test
            'maxie-authorize-host' => 'ssh-copy-id maxie@192.168.0.90',
            'maxie-test-auth'      => 'ssh -o BatchMode=yes maxie@192.168.0.90 "echo Auth Success"',
            'maxie-pull'      => 'ssh -o BatchMode=yes maxie@192.168.0.90 "cd sharpishly/ && git stash && git pull"',

            // Docker management on @maxie (Passwordless execution via sudoers)
            'docker-maxie'        => 'ssh -o ConnectTimeout=5 maxie "docker ps --format \'table {{.ID}}\t{{.Image}}\t{{.Status}}\t{{.Names}}\'"',
            'docker-maxie-status' => 'ssh -o BatchMode=yes maxie@192.168.0.90 "sudo systemctl status docker"',
            
            'gmail-inbox'   => 'himalaya envelope list',

            // Git log & failure-safe rebase pipeline (purges env.php tracking & bypasses local hook checks)
            'git-log'       => 'git log --oneline --graph --decorate',
            'git-rebase'    => 'git rm --cached env.php 2>/dev/null || true; git stash -u && git pull --rebase origin $(git branch --show-current) && git stash pop; git commit --no-verify -m "refactor(core): purge env.php from tracking and update terminal aliases" 2>/dev/null || true',

            // JSON and API Controller search
            'json-endpoints'   => "grep -R '\$this->json(' web/php/src/Controllers/",
            'api-controllers'  => "find web/php/src/Controllers/ -type f -iname '*apiController*' -exec grep -Hni 'json' {} +",

            // NMAP
            'nmap-local' => 'nmap 192.168.0.218 -sV -T4 2>&1',

            'nginx-header-check' => 'php diagnostics/nginx-header-check.php',

            // SSH Key Management & GitHub Verification on @maxie
            'maxie-keygen'        => 'ssh maxie@192.168.0.90 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && [ -f ~/.ssh/id_ed25519 ] || ssh-keygen -t ed25519 -C \"maxie@192.168.0.90\" -N \"\" -f ~/.ssh/id_ed25519"',
            'maxie-get-key'       => 'ssh maxie@192.168.0.90 "cat ~/.ssh/id_ed25519.pub"',
            'maxie-verify-github' => 'ssh -o BatchMode=yes maxie@192.168.0.90 "ssh -T -o StrictHostKeyChecking=accept-new git@github.com"',

            // Deploy Sharpishly repo to maxie@192.168.0.90
            'maxie-deploy-sharpishly' => 'ssh -o BatchMode=yes maxie@192.168.0.90 "rm -rf sharpishly && git clone git@github.com:Typekoce/SHARPISHLY-THOMASONS.git sharpishly && cd sharpishly && chmod +x final_installation.sh && ./final_installation.sh"',

            // Deployment support aliases
            'maxie-setup-sudoers' => 'ssh -t maxie@192.168.0.90 "echo \"maxie ALL=(ALL) NOPASSWD: ALL\" | sudo tee /etc/sudoers.d/maxie > /dev/null && sudo chmod 0440 /etc/sudoers.d/maxie"',
            'maxie-prep-ollama'   => 'ssh -o BatchMode=yes maxie@192.168.0.90 "ollama pull llama3 && ollama pull jina/jina-embeddings-v2-small-en"',
            'maxie-health-check'  => 'ssh -o BatchMode=yes maxie@192.168.0.90 "db-check && sudo systemctl status nginx"',

            // TestController
            'test-controller'   => 'curl -i http://localhost/php/test/test',

            // HealthController
            'health-controller' => 'curl -i http://localhost/php/health',
        );

        if (isset($terminal[$command])) {
            return $terminal[$command];
        }

        return false;
    }
}