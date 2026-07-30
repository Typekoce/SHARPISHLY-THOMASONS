<?php

namespace App\Controllers;

use App\Models\SnapshotsModel;
use Throwable;

class AgenticController extends BaseController
{
    /**
     * Agentic Dashboard / Platform Workflow Endpoint.
     * Route: /php/agentic
     */
    public function index(): void
    {
        $url = $this->request('url') ?? $_GET['url'] ?? 'https://uk.indeed.com/jobs?q=software+developer&vjk=45b268de52a0911b';

        try {
            $content = $this->getContents($url);

            if ($content === false) {
                $this->json(['status' => 'error', 'message' => 'Failed to read content from URL'], 500);
                return;
            }

            $model = new SnapshotsModel();
            
            $ts = date('Ymd_His');
            $registryId = $model->setSnapshotRegistry([
                'title'  => 'Agentic Capture',
                'status' => 'active',
            ]);

            $model->setSnapshot([
                'snapshots_id' => $registryId,
                'title'        => 'Agentic Ingest ' . $ts,
                'content'      => $content,
            ]);

            $this->json([
                'status'    => 'success',
                'timestamp' => $ts,
                'bytes'     => strlen($content),
                'message'   => 'Agentic payload ingested successfully',
            ]);
        } catch (Throwable $e) {
            if (isset($this->logger)) {
                $this->logger->log("AgenticController Error: " . $e->getMessage(), 'ERROR');
            }

            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch HTML content via cURL with custom headers.
     */
    public function getContents(string $url): string|false
    {
        $headers = [
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-GB,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Priority: u=1',
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_ENCODING       => '',
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}