<?php

declare(strict_types=1);

namespace App\Models;

class DashboardModel extends BaseModel
{
    /**
     * Fetch profile details and summary metrics for the authenticated user.
     */
    public function getUserDashboardData(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        // Retrieve user core record via framework ORM adapter (no raw SQL)
        $user = $this->orm->find('users', $userId);

        if (empty($user)) {
            return [];
        }

        // Aggregate lightweight metrics/dashboard state
        $stats = $this->orm->findAll('user_activity', [
            'user_id' => $userId,
            'limit'   => 5,
            'order'   => 'created_at DESC'
        ]);

        return [
            'id'       => $user['id'] ?? $userId,
            'email'    => $user['email'] ?? '',
            'name'     => $user['name'] ?? null,
            'stats'    => $stats ?? [],
        ];
    }
}