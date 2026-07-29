<?php

namespace App\Controllers;

use App\Models\IngestionModel;
use App\Models\SnapshotsModel;

class IngestionController extends BaseController 
{
    public $tbl = 'snapshots';

    /**
     * Synchronous Ingestion Endpoint.
     * Route: /ingestion?query=https://uk.indeed.com/jobs?q=software+developer
     */
    public function index(): void
    {
        $url = trim(
            $this->request('query') ?? 
            $this->request('url') ?? 
            ($_GET['query'] ?? '')
        );

        if ($url === '') {
            $this->json(['status' => 'error', 'message' => 'No URL provided'], 400);
            return;
        }
        
        $parser = new IngestionModel();
        $raw    = $parser->fetchRaw($url);

        if (!$raw) {
            if (isset($this->logger)) {
                $this->logger->log("Ingestion failed for URL: {$url}", 'ERROR');
            }
            $this->json(['status' => 'error', 'message' => 'Fetch failed or request was blocked by target host'], 500);
            return;
        }

        $ts = date('Ymd_His');

        $rawSuccess  = $this->snapshotsRaw($raw, $ts);
        $prepSuccess = $this->snapshots($raw, $ts);

        $model      = new SnapshotsModel();
        $registryId = $model->setSnapshotRegistry([
            'title'  => 'Form Capture',
            'status' => 'active'
        ]);

        $model->setSnapshot([
            'snapshots_id' => $registryId,
            'title'        => 'Page 1',
            'content'      => $raw
        ]);

        if (!$rawSuccess || !$prepSuccess) {
            $this->json([
                'status'     => 'partial_failure',
                'raw_saved'  => $rawSuccess,
                'prep_saved' => $prepSuccess,
                'message'    => 'Ingestion completed with storage warnings'
            ], 500);
            return;
        }

        $this->json([
            'status'    => 'success', 
            'timestamp' => $ts,
            'message'   => 'Raw and prepared snapshots saved successfully'
        ]);
    }

    /**
     * Asynchronous Supervisor Dispatch Endpoint (Strategy 1).
     * Route: /ingestion-async?query=https://...
     */
    public function queue(): void
    {
        $url = trim($this->request('query') ?? $this->request('url') ?? ($_GET['query'] ?? ''));

        if ($url === '') {
            $this->json(['status' => 'error', 'message' => 'No URL provided'], 400);
            return;
        }

        $ts         = date('Ymd_His');
        $scriptPath = $this->loc->storage("cmd/jobs/waiting/ingest_{$ts}.sh");
        $rawPath    = $this->loc->storage("snapshots/form_{$ts}.html");

        // Command executed by supervisor_worker.sh
        $cmd = sprintf(
            'curl -sL --compressed -A "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0" %s > %s',
            escapeshellarg($url),
            escapeshellarg($rawPath)
        );

        @mkdir(dirname($scriptPath), 0700, true);
        file_put_contents($scriptPath, "#!/bin/bash\n{$cmd}\n");
        chmod($scriptPath, 0755);

        $this->json([
            'status'    => 'queued',
            'timestamp' => $ts,
            'job_file'  => basename($scriptPath),
            'message'   => 'Ingestion task dispatched to supervisor worker',
        ]);
    }

    public function snapshotsRaw(string $content, string $ts): bool 
    {
        $path = $this->loc->storage("snapshots-raw/form_{$ts}.html");
        @mkdir(dirname($path), 0700, true);
        return file_put_contents($path, $content) !== false;
    }

    public function snapshots(string $content, string $ts): bool 
    {
        $path = $this->loc->storage("snapshots/form_{$ts}.html");
        @mkdir(dirname($path), 0700, true);
        $cleaned = $this->prepareFile($content);
        return file_put_contents($path, $cleaned) !== false;
    }

    public function prepareFile(string $content): string 
    {
        // If content is XML (RSS), bypass regular regex script trimming
        if (strpos($content, '<?xml') !== false || strpos($content, '<rss') !== false) {
            return trim($content);
        }

        $patterns = [
            '#<script\b[^>]*>.*?</script>#is',
            '#<style\b[^>]*>.*?</style>#is',
            '#<svg\b[^>]*>.*?</svg>#is',
            '#<noscript\b[^>]*>.*?</noscript>#is',
        ];
        return trim(preg_replace($patterns, '', $content));
    }
}