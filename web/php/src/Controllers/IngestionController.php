<?php

namespace App\Controllers;

use DOMDocument;
use DOMXPath;

class IndeedApiController extends BaseController
{
    /**
     * Reads ingested HTML snapshots from storage/snapshots and parses job listings.
     * Route: /indeed-api
     */
    public function index(): void
    {
        if (!isset($this->loc)) {
            $this->json(['success' => false, 'error' => 'location_service_missing'], 500);
            return;
        }

        $snapshotDir = $this->loc->storage('snapshots');
        $jobs = [];
        $maxJobs = 20;

        if (is_dir($snapshotDir)) {
            $files = glob($snapshotDir . '/*.html');
            if ($files !== false) {
                // Process newest snapshots first
                rsort($files);

                foreach ($files as $filePath) {
                    $parsedJobs = $this->parseSnapshotFile($filePath);
                    
                    foreach ($parsedJobs as $job) {
                        $jobs[] = $job;
                        if (count($jobs) >= $maxJobs) {
                            break 2; // Exit both file search and job extraction loops
                        }
                    }
                }
            }
        }

        $this->json([
            'success'     => true,
            'source'      => 'local_snapshots',
            'environment' => 'production',
            'count'       => count($jobs),
            'results'     => $jobs,
        ]);
    }

    /**
     * Parses stored HTML content to extract job cards using DOMXPath.
     */
    private function parseSnapshotFile(string $filePath): array
    {
        $html = file_get_contents($filePath);
        if (empty($html)) {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        
        // Target standard Indeed job card containers
        $nodes = $xpath->query("//div[contains(@class, 'job_seen_beacon')] | //div[contains(@class, 'cardOutline')] | //td[contains(@class, 'resultContent')]");

        if ($nodes->length === 0) {
            return [];
        }

        $jobs = [];
        $fileTag = basename($filePath, '.html');
        $snapshotDate = date('Y-m-d', filemtime($filePath));

        foreach ($nodes as $index => $node) {
            $titleNode   = $xpath->query(".//h2[contains(@class, 'jobTitle')]//span", $node)->item(0);
            $companyNode = $xpath->query(".//*[contains(@data-testid, 'company-name')]", $node)->item(0);
            $snippetNode = $xpath->query(".//*[contains(@class, 'underlining')] | .//div[contains(@class, 'job-snippet')]", $node)->item(0);
            $linkNode    = $xpath->query(".//h2[contains(@class, 'jobTitle')]//a", $node)->item(0);

            $role       = $titleNode ? trim($titleNode->textContent) : 'Software Developer';
            $company    = $companyNode ? trim($companyNode->textContent) : 'Indeed Employer';
            $rawSummary = $snippetNode ? trim($snippetNode->textContent) : 'No summary provided in snapshot.';
            
            // Normalize inner HTML whitespace and linebreaks
            $summary = preg_replace('/\s+/', ' ', $rawSummary);

            $url = $linkNode ? 'https://www.indeed.com' . $linkNode->getAttribute('href') : '#';

            $jobs[] = [
                'id'           => $fileTag . '_' . ($index + 1),
                'role'         => $role,
                'company'      => $company,
                'platform'     => 'Indeed',
                'summary'      => $summary,
                'url'          => $url,
                'status'       => 'pending',
                'status_label' => 'Snapshot Ingested',
                'applied_at'   => $snapshotDate,
                'has_cv'       => false,
            ];
        }

        return $jobs;
    }
}