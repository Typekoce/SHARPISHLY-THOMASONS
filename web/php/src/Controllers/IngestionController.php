<?php

namespace App\Controllers;

use App\Models\IngestionModel;

class IngestionController extends BaseController {

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

        $filename = 'form_' . date('Ymd_His') . '.html';
        $path = $this->loc->storage('snapshots/' . $filename);

        $html = $this->prepareFile($html);
        
        // 3. Commit to storage
        file_put_contents($path, $html);

        // 4. Save form field names to DB (Placeholder)
        // $this->saveFieldNamesToDb($html); 

        // 5. Respond
        return $this->json([
            'status' => 'success', 
            'file' => $filename,
            'message' => 'Form snapshotted and metadata logged'
        ]);
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
            'tbl' => 'forms',
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
}
