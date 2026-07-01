<?php

namespace App\Security;

class BaseDataModel {
    // Parameterized query wrapper to prevent SQLi
    protected function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Sanitization utility
    protected function clean($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}