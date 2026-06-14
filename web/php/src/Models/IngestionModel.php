<?php

namespace App\Models;

class IngestionModel extends BaseModel
{
    /**
     * Fetches raw HTML content from a URL
     */
    public function fetchRaw(string $url): string|false
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (compatible; IngestionBot/1.0)\r\n"
            ]
        ];
        return file_get_contents($url, false, stream_context_create($opts));
    }

    /**
     * Injects data into DOM nodes by name or id
     * @param \DOMDocument $dom
     * @param array $data
     * @return \DOMDocument
     */
    public function populateForm(\DOMDocument $dom, array $data): \DOMDocument
    {
        $xpath = new \DOMXPath($dom);
        foreach ($data as $name => $value) {
            $query = sprintf("//input[@name='%s' or @id='%s']", addslashes($name), addslashes($name));
            $nodes = $xpath->query($query);
            
            if ($nodes === false || $nodes->length === 0) {
                continue;
            }
            
            foreach ($nodes as $node) {
                $node->setAttribute('value', $value);
            }
        }
        return $dom;
    }
}