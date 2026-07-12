<?php

namespace App\Models;

/**
 * GoogleTokenModel
 * Centralizes persistence for Google OAuth tokens.
 * Uses deterministic upserts to manage token lifecycles.
 */
class GoogleTokenModel extends BaseModel
{
    /**
     * Store or update a token for a user.
     * Tokens are encrypted at rest; unique constraint ensures single record per user/provider.
     */
    public function saveToken(int $userId, string $provider, array $tokenData): bool
    {
        $encryptedAccess = $this->encrypt($tokenData['access_token']);
        $encryptedRefresh = isset($tokenData['refresh_token']) ? $this->encrypt($tokenData['refresh_token']) : null;
        $scopes = json_encode($tokenData['scopes'] ?? []);
        $expiresAt = $tokenData['expires_at'] ?? null;

        $sql = "INSERT INTO google_tokens 
                (user_id, provider, access_token, refresh_token, scopes, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    access_token = VALUES(access_token), 
                    refresh_token = VALUES(refresh_token), 
                    scopes = VALUES(scopes), 
                    expires_at = VALUES(expires_at),
                    revoked_at = NULL";
        
        return $this->db->execute($sql, [
            $userId, 
            $provider, 
            $encryptedAccess, 
            $encryptedRefresh, 
            $scopes, 
            $expiresAt
        ]);
    }

    /**
     * Retrieve a valid token for a user.
     */
    public function getToken(int $userId, string $provider): ?array
    {
        $sql = "SELECT * FROM google_tokens WHERE user_id = ? AND provider = ? AND revoked_at IS NULL";
        $data = $this->db->fetchOne($sql, [$userId, $provider]);
        
        if ($data) {
            $data['access_token'] = $this->decrypt($data['access_token']);
            $data['refresh_token'] = $this->decrypt($data['refresh_token']);
            return $data;
        }
        return null;
    }

    private function encrypt($data) { /* Implement your system's secure encryption logic */ }
    private function decrypt($data) { /* Implement your system's secure decryption logic */ }
}