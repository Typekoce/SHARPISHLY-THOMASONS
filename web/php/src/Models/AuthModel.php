<?php

declare(strict_types=1);

namespace App\Models;

class AuthModel extends BaseModel
{
    public bool $debug = false; 
    public string $tbl = 'auth';

    /**
     * Find existing auth record by provider/provider_uid or create new one.
     * Updates tokens on existing records.
     */
    public function findOrCreateOuser(array $profile): array
    {
        $existing = $this->db->find([
            'tbl'   => $this->tbl,
            'where' => [
                'provider'     => $profile['provider'],
                'provider_uid' => $profile['provider_uid'],
            ],
        ]);

        if (!empty($existing)) {
            $row = $existing[0];

            $update = [];
            if (!empty($profile['access_token'])) {
                $update['access_token'] = $profile['access_token'];
            }
            if (!empty($profile['refresh_token'])) {
                $update['refresh_token'] = $profile['refresh_token'];
            }
            if (!empty($profile['expires_at'])) {
                $update['expires_at'] = $profile['expires_at'];
            }

            if (!empty($update)) {
                $this->db->save($this->tbl, array_merge($update, [
                    'id' => (int) $row['id'],
                ]));
                
                $row = $this->db->find([
                    'tbl'   => $this->tbl,
                    'where' => ['id' => $row['id']],
                ])[0] ?? $row;
            }

            return $row;
        }

        $payload = [
            'provider'      => $profile['provider'],
            'provider_uid'  => $profile['provider_uid'],
            'email'         => $profile['email'] ?? null,
            'title'         => $profile['name'] ?? null,
            'access_token'  => $profile['access_token'] ?? null,
            'refresh_token' => $profile['refresh_token'] ?? null,
            'expires_at'    => $profile['expires_at'] ?? null,
            'status'        => 'active',
        ];

        $this->db->save($this->tbl, $payload);

        $created = $this->db->find([
            'tbl'   => $this->tbl,
            'where' => ['provider_uid' => $profile['provider_uid']],
        ]);

        return $created[0] ?? [];
    }
}