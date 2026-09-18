<?php

declare(strict_types=1);

namespace App\Controllers;

class BreadcrumbController extends BaseController
{
    /**
     * Resolve and return breadcrumbs for the current request.
     */
    public function index(): void
    {
        $customCrumbs = $this->request('breadcrumbs');

        // Restore dynamic resolution from request state, session, or URI
        if (!empty($customCrumbs) && is_array($customCrumbs)) {
            $crumbs = $customCrumbs;
            $this->session->set('breadcrumbs', $crumbs);
        } else {
            $crumbs = $this->session->get('breadcrumbs') ?? $this->buildFromUri();
        }

        // Standardize output via existing BaseController primitive
        if ($this->request('format') === 'json') {
            $this->json(['breadcrumbs' => $crumbs]);
            return;
        }

        $this->view('components/breadcrumbs', ['crumbs' => $crumbs]);
    }

    /**
     * Build standard hierarchical breadcrumb trail directly from URI path.
     */
    private function buildFromUri(): array
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $segments = array_filter(explode('/', trim((string) $uri, '/')));

        $crumbs = [
            ['label' => 'Home', 'url' => '/']
        ];

        $accPath = '';
        foreach ($segments as $segment) {
            $accPath .= '/' . $segment;
            $crumbs[] = [
                'label' => ucwords(str_replace(['-', '_'], ' ', $segment)),
                'url'   => $accPath
            ];
        }

        return $crumbs;
    }
}