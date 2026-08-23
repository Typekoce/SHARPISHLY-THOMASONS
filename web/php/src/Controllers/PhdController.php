<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PhdModel;
use App\Models\ScaffoldModel;
use Throwable;

class PhdController extends BaseController
{
    private PhdModel $phdModel;

    public function __construct()
    {
        parent::__construct();
        $this->phdModel = new PhdModel();
    }

    /**
     * Execute table schema migration via ScaffoldModel.
     */
    public function migrate(): void
    {
        try {
            $scaffold = new ScaffoldModel();
            $results  = $scaffold->syncSchema();
            $this->json(['status' => 'success', 'data' => $results]);
        } catch (Throwable $e) {
            $this->logger->error("PhdController migrate Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Schema migration failed'], 500);
        }
    }

    /**
     * Project membership authorization guard.
     */
    private function authorizeProjectAccess(int $projectId): bool
    {
        $userId = $this->session->get('user_id');
        return $userId !== null && $projectId > 0;
    }

    /**
     * Ingest paper metadata and check file hash uniqueness.
     */
    public function ingestSource(): void
    {
        $projectId = (int)$this->request('project_id');
        $title     = (string)$this->request('title');
        $fileHash  = (string)$this->request('file_hash');

        if (!$this->authorizeProjectAccess($projectId)) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized access'], 403);
            return;
        }

        try {
            $existing = $this->phdModel->findSourceByHash($projectId, $fileHash);
            if (!empty($existing['data'])) {
                $this->json([
                    'status'  => 'success', 
                    'data'    => $existing['data'], 
                    'message' => 'Source already registered'
                ]);
                return;
            }

            $response = $this->phdModel->createSource([
                'project_id' => $projectId,
                'title'      => $title,
                'file_hash'  => $fileHash,
                'doi_url'    => $this->request('doi_url')
            ]);

            $this->json(['status' => 'success', 'data' => $response]);
        } catch (Throwable $e) {
            $this->logger->error("ingestSource Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Internal processing error'], 500);
        }
    }

    /**
     * Synthesize draft notes via Ollama and persist draft notes via ORM.
     */
    public function synthesizeLiterature(): void
    {
        $projectId = (int)$this->request('project_id');
        $sourceId  = (int)$this->request('source_id');
        $chunkId   = (int)$this->request('chunk_id');

        if (!$this->authorizeProjectAccess($projectId)) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized access'], 403);
            return;
        }

        try {
            $payload = json_encode([
                'task'     => 'extract_claim',
                'chunk_id' => $chunkId
            ]);

            $rawResponse = $this->respond($payload, 'http://localhost:8765/rag/synthesize');
            $aiOutput    = json_decode((string)$rawResponse, true);

            $response = $this->phdModel->createNote([
                'project_id'        => $projectId,
                'source_id'         => $sourceId,
                'chunk_id'          => $chunkId,
                'claim'             => $aiOutput['claim'] ?? 'Draft extraction pending review',
                'evidence_location' => $aiOutput['location'] ?? 'Page 1',
                'status'            => 'draft'
            ]);

            $this->json(['status' => 'success', 'data' => $response]);
        } catch (Throwable $e) {
            $this->logger->error("synthesizeLiterature Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'Synthesis processing failed'], 500);
        }
    }
}