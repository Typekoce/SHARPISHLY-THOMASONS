<?php

namespace App\Controllers;

class VacanciesController extends BaseController {

    public function index($id = ''){

        $path = $this->getUrl();

        $this->setLog("NP Step 1: Get URL of vacancies");
        
        $file = file_get_contents($path);

        $this->setLog("NP Step 2: Get contents");

        $file = $this->cleanHtml($file);

        $this->saveRawHtml($file);

        $this->setLog("NP Step 3: Save raw data to required directory");

        ///echo $file;

    }

    public function setLog($msg){
        $this->logger->log($msg, 'INFO');
    }

    public function getUrl(){

        $url = 'https://www.jobswipe.net/exit/?jobHash=716F0C4F180E1D7DCB11D49BBEAF4258&utm_medium=aggregator&source=CLICKTH-GB&utm_campaign=CLICKTH-GB-JLO&click_id=411c244cd5e29ec8f7cca00e1667e3c78d7dabea-554';

        return $url;

    }

    public function saveRawHtml($file){

        echo $path =$this->location->storage('job_apply/01_webdesigner/raw.html');

        file_put_contents($path,$file);

    }

    public function cleanHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Remove content that should never enter the RAG corpus.
        $html = preg_replace(
            [
                '/<script\b[^>]*>.*?<\/script\s*>/is',
                '/<style\b[^>]*>.*?<\/style\s*>/is',
                '/<nav\b[^>]*>.*?<\/nav\s*>/is',
                '/<footer\b[^>]*>.*?<\/footer\s*>/is',
                '/<header\b[^>]*>.*?<\/header\s*>/is',
                '/<noscript\b[^>]*>.*?<\/noscript\s*>/is',
                '/<!--.*?-->/s'
            ],
            '',
            $html
        );

        if ($html === null) {
            throw new \RuntimeException('Unable to remove HTML noise');
        }

        // Preserve meaningful structure.
        $html = preg_replace(
            [
                '/<\s*br\s*\/?\s*>/i',
                '/<\s*\/\s*(p|div|section|article|tr|li|h[1-6]|ul|ol|dt|dd)\s*>/i',
                '/<\s*(li)\b[^>]*>/i'
            ],
            [
                "\n",
                "\n",
                "\n- "
            ],
            $html
        );

        if ($html === null) {
            throw new \RuntimeException('Unable to preserve HTML structure');
        }

        // Remove remaining tags.
        $text = preg_replace('/<[^>]*>/s', '', $html);

        if ($text === null) {
            throw new \RuntimeException('Unable to strip HTML tags');
        }

        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
            'UTF-8'
        );

        // Normalise non-breaking spaces and line endings.
        $text = str_replace(
            ["\xC2\xA0", "\r\n", "\r"],
            [' ', "\n", "\n"],
            $text
        );

        $lines = preg_split('/\n+/', $text) ?: [];
        $cleanedLines = [];

        foreach ($lines as $line) {
            $line = preg_replace('/[ \t]+/', ' ', $line) ?? '';
            $line = trim($line);

            if ($line !== '') {
                $cleanedLines[] = $line;
            }
        }

        return trim(implode("\n", $cleanedLines));
    }

}