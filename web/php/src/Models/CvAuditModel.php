<?php

namespace App\Models;

/**
 * CvAuditModel
 * Handles cross-cutting concerns like logging tailoring actions.
 */
class CvAuditModel extends BaseModel {
    public function logTailoring(string $vacancyPath, array $result): void {
        $conditions = [
            'title'      => 'cv-tailoring',
            'message'    => $vacancyPath,
            'content'    => json_encode($result),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->save('queries', $conditions);
    }
}