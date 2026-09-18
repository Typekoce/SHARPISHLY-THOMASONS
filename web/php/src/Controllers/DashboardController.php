<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new DashboardModel();
    }

    /**
     * Render main authenticated workspace/dashboard route (/).
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');

        // Guard unauthenticated access
        if (empty($userId)) {
            header('Location: /login');
            exit;
        }

        $userData = $this->model->getUserDashboardData((int) $userId);

        if (empty($userData)) {
            $this->session->remove('user_id');
            header('Location: /login?error=user_not_found');
            exit;
        }

        $payload = [
            'user' => [
                'id'       => $userData['id'],
                'email'    => $userData['email'],
                'name'     => $userData['name'] ?? 'User',
                'provider' => $this->session->get('provider'),
            ],
            'stats' => $userData['stats'] ?? [],
        ];

        // Serve JSON response for SPA/front-end state, or render view via framework primitives
        if ($this->request('format') === 'json') {
            $this->json($payload);
            return;
        }

        $this->view('dashboard/index', $payload);
    }
}