<?php

namespace App\Controllers;

class SalesController extends BaseController {

    public $tbl = 'sales';
    public $leadsTbl = 'leads';

    // List all sales
    public function index() {
        $conditions = [
            'tbl' => $this->tbl,
            'order' => ['id' => 'DESC'],
        ];
        $rs = $this->db->find($conditions);
        $this->json($rs);
    }

    // Create a new sale entry
    public function save() {
        $data = $this->request(null);
        if (!$data) return;

        // Save to sales table
        $id = $this->db->save($this->tbl, $data);
        
        $this->json(['status' => 'success', 'id' => $id]);
    }

    // Log a new lead or customer recommendation
    public function logLead() {
        $data = $this->request(null);
        if (!$data) return;

        // Enforce basic structure
        $lead = [
            'source'      => $data['source'] ?? 'public',
            'notes'       => $data['notes'] ?? '',
            'created_at'  => $this->now()
        ];

        $id = $this->db->save($this->leadsTbl, $lead);
        $this->json(['status' => 'success', 'lead_id' => $id]);
    }

    // Fetch all leads for the UI feed
    public function getLeads() {
        $rs = $this->db->find(['tbl' => $this->leadsTbl, 'order' => ['id' => 'DESC']]);
        $this->json($rs);
    }
}