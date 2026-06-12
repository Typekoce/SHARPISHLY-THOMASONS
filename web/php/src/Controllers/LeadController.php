<?php

// web/php/app/Controllers/LeadController.php
namespace App\Controllers;

class LeadController extends BaseController {
    public function index() {
        $this->json($this->db->find(['tbl' => 'leads']));
    }

    public function log() {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $this->db->save('leads', $data);
        $this->json(['status' => 'success', 'id' => $id]);
    }
}