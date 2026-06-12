<?php

namespace App\Controllers;

use App\Models\IngestionModel;

class IngestionController extends BaseController {

    public $tbl = 'html_ingestions';

    /**
     * Corresponds to /ingestions
     */
    public function index() {
        $conditions = [
            'tbl'   => $this->tbl,
            'order' => ['id' => 'DESC'],
        ];
        
        $rs = $this->db->find($conditions);
        $this->json($rs);
    }

    /**
     * Corresponds to /ingest
     */
    public function save($url = '') {


        $data = ['url' => $_GET['url']];

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (compatible; IngestionBot/1.0)\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $html = file_get_contents($data['url'], false, $context);


        if ($html === false) {
            return ['error' => 'Failed to retrieve content'];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $titleNode = $dom->getElementsByTagName('title')->item(0);
        $descriptionNode = $xpath->query("//meta[@name='description']/@content")->item(0);

        //$data['test'] = $html;

        //$data['description'] $descriptionNode;

        $data['title'] = $titleNode;

        $this->dBug($data);die();
        

        // $data = $this->request(null);
        
        // if (!$data || !isset($data['url'])) {
        //     $this->json(['status' => 'error', 'message' => 'Missing URL'], 400);
        //     return;
        // }

        $parser = new IngestionModel();
        
        $parsedContent = $parser->fetchAndParse($data['url']);

        $record = [
            'url'        => $data['url'],
            'content'    => json_encode($parsedContent),
            'created_at' => $this->now()
        ];

        $this->json($record);die();

        $id = $this->db->save($this->tbl, $record);
        
        $this->json(['status' => 'success', 'id' => $id]);
    }
}