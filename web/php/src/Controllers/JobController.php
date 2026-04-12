<?php

namespace App\Controllers;

class JobController extends BaseController {

    public function index(){

        $conditions = array(
            'tbl'=>'jobs',
            'where'=>array('status'=>'pending'),
            'order'=>array('id'=>'DESC'),
            'limit'=>1
        );


        $data = $this->db->find($conditions);

        $this->json($data);

    }

    /**
     * Mock method to trigger the Neural Pipeline
     */
    public function create() {
        
        $payload = json_encode([
            'path' => '/var/www/html/storage/uploads/test.csv',
            'type' => 'csv',
            'created_by' => 'system_mock'
        ]);

        $data = [
            // 'tbl' => 'jobs',
            'payload' => $payload,
            'status'  => 'pending',
            'file_name' => 'test.csv' 
        ];

        // Using your standard insert logic
        $result = $this->db->save('jobs',$data);

        return $this->json([
            'status' => 'success', 
            'message' => 'Job posted to the DMZ.',
            'job_id' => $result
        ]);
    }

    public function update($id)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$id || !is_numeric($id)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid ID'], 400);
        }

        // Whitelist check
        $allowed = ['pending', 'processing', 'completed', 'failed'];
        if (!isset($data['status']) || !in_array($data['status'], $allowed, true)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid status'], 400);
        }

        $result = $this->db->save('jobs', [
            'id'     => (int)$id,
            'status' => $data['status']
            // Only add 'error_message' if the column exists in your DB!
        ]);

        return $result 
            ? $this->json(['status' => 'success', 'message' => "Job $id updated"])
            : $this->json(['status' => 'error', 'message' => 'Update failed'], 500);
    }

}