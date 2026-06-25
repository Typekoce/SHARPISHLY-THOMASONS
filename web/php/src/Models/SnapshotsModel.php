<?php

namespace App\Models;

class SnapshotsModel extends BaseModel {

    /**
     * Save the parent registry record
     */
    public function setSnapshotRegistry(array $data) {
        // Simple save: returns ID or true on success
        return $this->db->save('snapshots', $data);
    }

    /**
     * Save the child page snapshot record
     */
    public function setSnapshot(array $data) {
        return $this->db->save('snapshot', $data);
    }

    /**
     * Retrieve data based on table and criteria
     */
    public function get(string $table, array $where) {
        return $this->db->find([
            'tbl'   => $table,
            'where' => $where
        ]);
    }
}