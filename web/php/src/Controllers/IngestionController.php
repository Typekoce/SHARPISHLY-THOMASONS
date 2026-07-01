<?php

namespace App\Controllers;

use App\Models\IngestionModel;
use App\Models\SnapshotsModel;

class IngestionController extends BaseController {

    public $tbl = 'snapshots';

    public function index() {
        // 1. Retrieve the URL directly from the query parameter
        $url = $_GET['query'] ?? '';

        if (empty($url)) {
            return $this->json(['error' => 'No URL provided'], 400);
        }
        
        $parser = new IngestionModel();
        
        $html = $parser->fetchRaw($url);
        if (!$html) {
            return $this->json(['status' => 'error', 'message' => 'Fetch failed'], 500);
        }

        $ts = date('Ymd_His');

        // 1. Save Raw (Tier 1)
        $rawSuccess = $this->snapshotsRaw($html, $ts);
        
        // 2. Save Prepared (Tier 2)
        $prepSuccess = $this->snapshots($html, $ts);


        $model = new SnapshotsModel();

        // 1. Create the parent entry
        $registryId = $model->setSnapshotRegistry([
            'title' => 'Form Capture',
            'status' => 'active'
        ]);

        // 2. Create the child entry linked to that ID
        $model->setSnapshot([
            'snapshots_id' => $registryId,
            'title'        => 'Page 1',
            'content'      => $html // The cleaned/raw HTML
        ]);

        // 3. Partial Failure Handling
        if (!$rawSuccess || !$prepSuccess) {
            return $this->json([
                'status' => 'partial_failure',
                'raw_saved' => $rawSuccess,
                'prep_saved' => $prepSuccess,
                'message' => 'Ingestion completed with storage warnings'
            ], 500);
        }

        return $this->json([
            'status' => 'success', 
            'timestamp' => $ts,
            'message' => 'Raw and prepared snapshots saved successfully'
        ]);

    }

    /**
     * Display all records, useful for debugging
     */
    public function records($id = '')
    {
        $conditions = [
            'tbl'   => $this->tbl,
            'join'  => [
                'table' => 'snapshot', // Ensure this table name is correct
                'on'    => 'snapshots.id = snapshot.snapshots_id',
                'type'  => 'LEFT'
            ],
            // Use * carefully if you suspect schema mismatch
            'fields' => ['snapshots.*'] 
        ];

        $res = $this->db->find($conditions);

        return $this->json($res);
    }

    /**
     * Display all records, useful for debugging
     */
    public function test($id = ''){

        $data = array(
            'id' => $id,
            'request' => $this->request(),
        );

        $this->json($data);
    }

    public function snapshotsRaw($html, $ts) {
        $path = $this->loc->storage("snapshots-raw/form_{$ts}.html");
        return file_put_contents($path, $html) !== false;
    }

    public function snapshots($html, $ts) {
        $path = $this->loc->storage("snapshots/form_{$ts}.html");
        $cleaned = $this->prepareFile($html);
        return file_put_contents($path, $cleaned) !== false;
    }

    public function prepareFile($html) {
        // Using PHP-compatible PCRE syntax (i = case insensitive, s = dot matches newline)
        $patterns = [
            '#<script\b[^>]*>.*?</script>#is',
            '#<style\b[^>]*>.*?</style>#is',
            '#<svg\b[^>]*>.*?</svg>#is',
            '#<noscript\b[^>]*>.*?</noscript>#is',
        ];
        $cleaned = preg_replace($patterns, '', $html);
        return trim($cleaned);
    }

    public function setFields($data){

        // ask rag to find form fields and correctly map them to data array
        $conditions = array(
            'tbl' => $this->tbl,
            'where' => array('id' => 1)
        );

        $res = $this->db->find($conditions);

        // map form field names to data
        //  map($data,$res);


        return $data;
    }

    public function save() {
        //http://192.168.0.218/php/ingestion/save/?url=https://www.applybe.com/?a=145F80311.0
        $url = $_GET['url'] ?? '';
        
        $data = [
            'FirstName' => 'Paul', 
            'email' => 'paul@sharpishly.com'
        ];

        $data = 

        $parser = new IngestionModel();
        $html = $parser->fetchRaw($url);

        if ($html === false) {
            $this->json(['error' => 'Failed to retrieve content'], 500);
            return;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Pass the DOM to the model to handle the transformation
        $dom = $parser->populateForm($dom, $data);

        header('Content-Type: text/html; charset=UTF-8');
        echo $dom->saveHTML();
        exit;
    }

    /**
     * GET /php/job/payload/{id}
     * Streams the raw BLOB data from MariaDB to the requester.
     */
    public function payload($id)
    {
        $conditions = [
            'tbl'   => $this->forms,
            'where' => ['id' => (int)$id]
        ];

        $jobResult = $this->db->find($conditions);
        $job = $jobResult[0] ?? null; 

        if (!$job || empty($job['payload'])) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Payload not found or empty'
            ], 404);
        }
        
        return $this->json(['status' => 'success', 'payload' => $job['payload']]);
    }


    /**
     * PUT /php/job/update/{id}
     * Persists neural chunks to MariaDB via framework repository layer.
     */
    public function update($id)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?? [];
        
        $id = (int)$id;
        $status = $data['status'] ?? 'unknown';

        $updateData = [
            'id'     => $id,
            'status' => $status
        ];

        if ($status === 'completed' || $status === 'failed') {
            $updateData['finished_at'] = date('Y-m-d H:i:s');
        }

        $this->logger->log("NP Step 4: Update received from Python Worker for Job ID: {$id}", 'INFO');

        // Update Job state metrics
        $result = $this->db->save('jobs', $updateData);

        // Sync Vectors directly to MariaDB safely (No raw SQL)
        if (!empty($data['chunks']) && is_array($data['chunks'])) {
            foreach ($data['chunks'] as $chunk) {
                $this->db->save('vectors', [
                    'job_id'    => $id,
                    'content'   => $chunk['content'] ?? '',
                    'embedding' => json_encode($chunk['embedding'] ?? []),
                    'pref'      => $chunk['pref'] ?? null
                ]);
            }
        }

        if ($result === false) {
            $this->logger->log("NP Step 4 Failed: MariaDB update failed to write for job {$id}", 'ERROR');
            return $this->json(['status' => 'error', 'message' => 'DB Save Failed'], 500);
        }

        return $this->json([
            'status' => 'success', 
            'job_id' => $id, 
            'chunks_synced' => count($data['chunks'] ?? [])
        ]);
    }

}
