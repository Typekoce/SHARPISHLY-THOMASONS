<?php

namespace App\Models;

class GmailModel extends BaseModel {
    public function getProfile(string $token): array {
        // Just point to the API and use your pipe
        return $this->request("https://gmail.googleapis.com/gmail/v1/users/me/profile", $token);
    }
}