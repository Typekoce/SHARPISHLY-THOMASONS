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

public function update($id) {
    // Get the JSON body from the Python request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$id || !isset($data['status'])) {
        return $this->json(['status' => 'error', 'message' => 'Invalid update data'], 400);
    }

    // Update the record in the DMZ
    $this->db->save('jobs', [
        'id' => $id,
        'status' => $data['status']
    ]);

    return $this->json([
        'status' => 'success',
        'message' => "Job $id updated to " . $data['status']
    ]);
}

}