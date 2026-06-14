<?php

namespace App\Controllers;

use App\Models\IngestionModel;

class IngestionController extends BaseController {

    public function save() {
        $url = $_GET['url'] ?? '';
        $data = [
            'FirstName' => 'Paul', 
            'email' => 'paul@sharpishly.com'
        ];

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