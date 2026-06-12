<?php

namespace App\Models;

class IngestionModel extends BaseModel
{
    /**
     * Fetches URL content and parses specific elements
     * @param string $url
     * @return array
     */
    public function fetchAndParse(string $url): array
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (compatible; IngestionBot/1.0)\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $html = file_get_contents($url, false, $context);

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

        return [
            'title' => $titleNode ? $titleNode->nodeValue : '',
            'description' => $descriptionNode ? $descriptionNode->nodeValue : '',
        ];
    }
}