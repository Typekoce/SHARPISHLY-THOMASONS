<?php

namespace App\Controllers;

use DOMDocument;
use DOMXPath;
use SimpleXMLElement;

class IndeedApiController extends BaseController
{
    /**
     * Reads local snapshots and returns parsed job listing objects.
     * Route: /indeed-api
     */
    public function index(): void
    {
        if (!isset($this->loc)) {
            $this->json(['success' => false, 'error' => 'location_service_missing'], 500);
            return;
        }

        $snapshotDir = $this->loc->storage('snapshots');
        $jobs        = [];
        $maxJobs     = 20;
        $primarySource = 'local_snapshots';

        if (is_dir($snapshotDir)) {
            $files = glob($snapshotDir . '/*.html');
            if ($files !== false) {
                rsort($files); // Process newest snapshots first

                foreach ($files as $filePath) {
                    $parsedJobs = $this->parseSnapshotFile($filePath);
                    
                    foreach ($parsedJobs as $job) {
                        $jobs[] = $job;
                        if (count($jobs) >= $maxJobs) {
                            break 2;
                        }
                    }
                }
            }
        }

        $this->json([
            'success'     => true,
            'source'      => $primarySource,
            'environment' => 'production',
            'count'       => count($jobs),
            'results'     => $jobs,
        ]);
    }

    /**
     * Inspects file header and routes to either HTML DOM or XML RSS parser.
     */
    private function parseSnapshotFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (empty($content)) {
            return [];
        }

        // Detect RSS vs HTML
        if (strpos($content, '<rss') !== false || strpos($content, '<?xml') !== false) {
            return $this->parseRssSnapshot($content, $filePath);
        }

        return $this->parseHtmlSnapshot($content, $filePath);
    }

    /**
     * Parse DOM-based HTML snapshots (Supervisor / Headless Chrome).
     */
    private function parseHtmlSnapshot(string $html, string $filePath): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//div[contains(@class, 'job_seen_beacon')] | //div[contains(@class, 'cardOutline')] | //td[contains(@class, 'resultContent')]");

        if ($nodes->length === 0) {
            return [];
        }

        $jobs         = [];
        $fileTag      = basename($filePath, '.html');
        $snapshotDate = date('Y-m-d', filemtime($filePath));

        foreach ($nodes as $index => $node) {
            $titleNode   = $xpath->query(".//h2[contains(@class, 'jobTitle')]//span", $node)->item(0);
            $companyNode = $xpath->query(".//*[contains(@data-testid, 'company-name')]", $node)->item(0);
            $snippetNode = $xpath->query(".//*[contains(@class, 'underlining')] | .//div[contains(@class, 'job-snippet')]", $node)->item(0);
            $linkNode    = $xpath->query(".//h2[contains(@class, 'jobTitle')]//a", $node)->item(0);

            $role       = $titleNode ? trim($titleNode->textContent) : 'Software Developer';
            $company    = $companyNode ? trim($companyNode->textContent) : 'Indeed Employer';
            $rawSummary = $snippetNode ? trim($snippetNode->textContent) : 'No summary provided in snapshot.';
            $summary    = preg_replace('/\s+/', ' ', $rawSummary);
            $url        = $linkNode ? 'https://uk.indeed.com' . $linkNode->getAttribute('href') : '#';

            $jobs[] = [
                'id'           => $fileTag . '_html_' . ($index + 1),
                'role'         => $role,
                'company'      => $company,
                'platform'     => 'Indeed (HTML Snapshot)',
                'summary'      => $summary,
                'url'          => $url,
                'status'       => 'pending',
                'status_label' => 'HTML Ingested',
                'applied_at'   => $snapshotDate,
                'has_cv'       => false,
            ];
        }

        return $jobs;
    }

    /**
     * Parse XML RSS snapshots (Synchronous cURL Ingestion).
     */
    private function parseRssSnapshot(string $xmlContent, string $filePath): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false || !isset($xml->channel->item)) {
            return [];
        }

        $jobs         = [];
        $fileTag      = basename($filePath, '.html');
        $snapshotDate = date('Y-m-d', filemtime($filePath));
        $counter      = 0;

        foreach ($xml->channel->item as $item) {
            $counter++;
            $title       = (string) $item->title;
            $link        = (string) $item->link;
            $description = preg_replace('/\s+/', ' ', strip_tags((string) $item->description));
            $company     = 'Indeed Employer';

            if (strpos($title, ' - ') !== false) {
                $parts   = explode(' - ', $title);
                $title   = trim($parts[0]);
                $company = trim($parts[1]);
            }

            $jobs[] = [
                'id'           => $fileTag . '_rss_' . $counter,
                'role'         => $title,
                'company'      => $company,
                'platform'     => 'Indeed (RSS Snapshot)',
                'summary'      => substr($description, 0, 180) . '...',
                'url'          => $link,
                'status'       => 'pending',
                'status_label' => 'RSS Ingested',
                'applied_at'   => $snapshotDate,
                'has_cv'       => false,
            ];
        }

        return $jobs;
    }
}