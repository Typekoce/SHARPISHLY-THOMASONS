<?php

namespace App\Controllers;

use SimpleXMLElement;

class IndeedApiController extends BaseController
{
    /**
     * Entry point: Fetches and displays software developer jobs.
     * Route: /indeed-api?q=Software+Developer&location=United+Kingdom
     */
    public function index(): void
    {
        $query    = trim($this->request('q') ?? 'Software Developer');
        $location = trim($this->request('location') ?? 'United Kingdom');

        $token  = $this->getLocalToken();
        $source = 'mock_fallback';
        $jobs   = [];

        if ($token !== null) {
            $source = 'indeed_api';
            $jobs   = $this->fetchFromIndeedApi($query, $location, $token);
        } else {
            $jobs = $this->fetchFromIndeedRss($query, $location);
            if (!empty($jobs)) {
                $source = 'indeed_rss';
            }
        }

        // Safe deterministic fallback if upstream feeds are empty or fail
        if (empty($jobs)) {
            $source = 'mock_fallback';
            $jobs   = $this->getMockFallbackJobs();
        }

        $this->json([
            'success'      => true,
            'source'       => $source,
            'token_active' => $token !== null,
            'query'        => [
                'q'        => $query,
                'location' => $location,
            ],
            'results'      => $jobs,
        ]);
    }

    /**
     * Ingest live roles via standard XML RSS feed.
     */
    private function fetchFromIndeedRss(string $query, string $location): array
    {
        $url = 'https://www.indeed.com/rss?' . http_build_query([
            'q' => $query,
            'l' => $location,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $xmlString = curl_exec($ch);
        $errno     = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || empty($xmlString)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        if ($xml === false || !isset($xml->channel->item)) {
            return [];
        }

        $jobs = [];
        $idCounter = 100;

        foreach ($xml->channel->item as $item) {
            $idCounter++;
            $title       = (string) $item->title;
            $link        = (string) $item->link;
            $description = strip_tags((string) $item->description);
            $pubDate     = (string) $item->pubDate;

            $company = 'Indeed Employer';
            if (strpos($title, ' - ') !== false) {
                $parts   = explode(' - ', $title);
                $title   = trim($parts[0]);
                $company = trim($parts[1]);
            }

            $jobs[] = [
                'id'           => $idCounter,
                'role'         => $title,
                'company'      => $company,
                'platform'     => 'Indeed',
                'summary'      => substr($description, 0, 180) . '...',
                'url'          => $link,
                'status'       => 'pending',
                'status_label' => 'Available',
                'applied_at'   => !empty($pubDate) ? date('Y-m-d', strtotime($pubDate)) : date('Y-m-d'),
                'has_cv'       => false,
            ];

            if (count($jobs) >= 10) {
                break;
            }
        }

        return $jobs;
    }

    /**
     * Experimental stub for Partner API integration once partner app GraphQL/REST docs are finalized.
     */
    private function fetchFromIndeedApi(string $query, string $location, string $token): array
    {
        // Placeholder endpoint structure pending partner onboarding
        $url = 'https://apis.indeed.com/v2/jobs?' . http_build_query([
            'q' => $query,
            'l' => $location,
        ]);

        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || !$response) {
            return [];
        }

        $data = json_decode($response, true);
        if (!isset($data['results']) || !is_array($data['results'])) {
            return [];
        }

        $jobs = [];
        foreach ($data['results'] as $idx => $job) {
            $jobs[] = [
                'id'           => $job['jobkey'] ?? ($idx + 1),
                'role'         => $job['jobtitle'] ?? 'Software Developer',
                'company'      => $job['company'] ?? 'Unknown Company',
                'platform'     => 'Indeed API',
                'summary'      => $job['snippet'] ?? 'No job summary provided.',
                'url'          => $job['url'] ?? '#',
                'status'       => 'pending',
                'status_label' => 'Available',
                'applied_at'   => $job['formattedRelativeTime'] ?? date('Y-m-d'),
                'has_cv'       => false,
            ];
        }

        return $jobs;
    }

    private function getMockFallbackJobs(): array
    {
        return [
            [
                'id'           => 1,
                'role'         => 'Senior PHP / C++ Developer',
                'company'      => 'Apex Systems',
                'platform'     => 'Indeed',
                'summary'      => 'Looking for a developer to build lightweight MVC frameworks and robust back-end APIs without heavy third-party dependencies.',
                'status'       => 'applied',
                'status_label' => 'Applied',
                'applied_at'   => '2026-07-28',
                'has_cv'       => true,
            ],
            [
                'id'           => 2,
                'role'         => 'Full Stack Software Engineer',
                'company'      => 'Nexus Tech UK',
                'platform'     => 'Indeed',
                'summary'      => 'Maintain clean asynchronous job queue state machines and custom micro-controller integrations.',
                'status'       => 'pending',
                'status_label' => 'Generating CV',
                'applied_at'   => 'Pending',
                'has_cv'       => false,
            ],
        ];
    }

    private function getLocalToken(): ?string
    {
        $path = $this->loc->storage('indeed/tokens/access_token.txt');
        return is_file($path) ? trim(file_get_contents($path)) : null;
    }
}