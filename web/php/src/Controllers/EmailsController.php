<?php

namespace App\Controllers;
//TODO: Migrate functionality to EmailsModel
class EmailsController extends BaseController {

	public function index(){

	}


public function queue($id = '') {

    $data = $this->request(null);

    if (!$data) return;

    $data['id'] = $id;


    // Use the Location service's storage method.
    // This returns the full path and handles recursive directory creation for you.
    $dir = $this->loc->storage('tasks/pending/email/');
    $filePath = $dir . $id . '.json';

    // Attempt the write
    if (file_put_contents($filePath, json_encode($data), LOCK_EX)) {
        // Return success and finish
        $this->json(['status' => 'success', 'id' => $id, 'data' => $data]);
    } else {
        // Return error and finish
        $this->json(['status' => 'error', 'message' => 'Write failed']);
    }
}


public function old_queue() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        return;
    }

    // Path aligned with agents_workflow.sh
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/../storage/tasks/pending/email';
    $filePath = $dir . '/' . $data['id'] . '.json';

    // Atomic write
    if (file_put_contents($filePath, json_encode($data), LOCK_EX) !== false) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'id' => $data['id']]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Persistence failure']);
    }
}

	public function test($post = ''){
		$data =array('id'=>'');
/**
		$conditions = array(
			'email' 	=> 'paul+test@sharpishly.com',
			'message'	=> 'Hello World',
			'created_at'	=> $this->now(),
			'status'	=> 'waiting'
		);
**/

		$conditions = json_decode($post);

		$conditions['status'] = 'waiting';

		$conditions['created_at'] = $this->now();

		$id = $this->db->save('emails',$conditions);

		$data['id'] = $id;

		$data = $data + $conditions;

		$data = $this->job($data);

		$this->json($data);

	}

	public function job($data){

	 $file = $this->loc->storage('agents/emails/waiting/job_' . $data['id'] . '.json');

         file_put_contents($file,json_encode($data));

	 return $data;

	}

}// end of class
