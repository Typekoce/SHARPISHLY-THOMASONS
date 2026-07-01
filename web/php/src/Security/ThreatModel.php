<?php

namespace App\Security;

// File: models/ThreatModel.php
class ThreatModel extends BaseDataModel {
    // Ensures we return an array, keeping the contract predictable
    public function logThreat(string $type, string $payload, string $source): void {
        $sql = "INSERT INTO threats (type, payload, source, created_at) 
                VALUES (:type, :payload, :source, NOW())";
        $this->query($sql, [
            ':type'    => $type,
            ':payload' => $payload,
            ':source'  => $source
        ]);
    }

    public function getThreats(): array {
        $stmt = $this->query("SELECT * FROM threats ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}