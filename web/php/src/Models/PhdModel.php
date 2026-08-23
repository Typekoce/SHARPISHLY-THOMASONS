<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Orm;
use Throwable;

class PhdModel extends BaseModel
{
    protected Orm $orm;

    public function __construct()
    {
        parent::__construct();
        $this->orm = new Orm();
    }

    /**
     * Register a new research source.
     */
    public function createSource(array $data): array
    {
        $conditions = [
            'source' => 'phd_research_sources',
            'method' => 'POST',
            'data'   => [
                'project_id'      => $data['project_id'],
                'title'           => $data['title'],
                'authors'         => $data['authors'] ?? null,
                'doi_url'         => $data['doi_url'] ?? null,
                'file_hash'       => $data['file_hash'],
                'access_status'   => $data['access_status'] ?? 'open',
                'ingestion_state' => 'pending'
            ]
        ];

        return $this->orm->execute($conditions);
    }

    /**
     * Save an extracted literature chunk.
     */
    public function saveChunk(array $chunk): array
    {
        $conditions = [
            'source' => 'phd_literature_chunks',
            'method' => 'POST',
            'data'   => [
                'source_id'       => $chunk['source_id'],
                'chunk_index'     => $chunk['chunk_index'],
                'chunk_text'      => $chunk['chunk_text'],
                'page_number'     => $chunk['page_number'] ?? null,
                'vector_id'       => $chunk['vector_id'],
                'embedding_model' => $chunk['embedding_model'] ?? 'nomic-embed-text',
                'checksum'        => md5($chunk['chunk_text'])
            ]
        ];

        return $this->orm->execute($conditions);
    }

    /**
     * Create an evidence-linked research note.
     */
    public function createNote(array $data): array
    {
        $conditions = [
            'source' => 'phd_research_notes',
            'method' => 'POST',
            'data'   => [
                'project_id'        => $data['project_id'],
                'source_id'         => $data['source_id'],
                'chunk_id'          => $data['chunk_id'] ?? null,
                'note_type'         => $data['note_type'] ?? 'claim',
                'claim'             => $data['claim'],
                'evidence_location' => $data['evidence_location'] ?? null,
                'tags'              => $data['tags'] ?? null,
                'confidence_score'  => $data['confidence_score'] ?? 1.00,
                'status'            => $data['status'] ?? 'draft'
            ]
        ];

        return $this->orm->execute($conditions);
    }

    /**
     * Check if a file source already exists by file hash.
     */
    public function findSourceByHash(int $projectId, string $hash): array
    {
        $conditions = [
            'source' => 'phd_research_sources',
            'method' => 'GET',
            'data'   => [
                'project_id' => $projectId,
                'file_hash'  => $hash
            ]
        ];

        return $this->orm->execute($conditions);
    }
}