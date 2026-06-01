<?php

namespace App\Controllers;

class AgentController extends BaseController {

	public $tbl = 'agents';

	public function index(){
		//TODO: Move functionality to AgentModel. Display all agent task
		$conditions = array(
			'tbl'	=> $this->tbl,
			'order'	=> array('id'	=> 'DESC'),
		);

		$rs = $this->db->find($conditions);

		$this->json($rs);
	}


public function test($post = ''){
    // 1. If $post is empty, try to get the raw body
    if (empty($post)) {
        $post = file_get_contents('php://input');
    }

    // 2. Debugging: Log what is actually arriving
    if (empty($post)) {
        $this->json(['error' => 'No data received']);
        return;
    }

    $data = array('id' => '');
    $conditions = json_decode($post, true);

    // 3. Ensure JSON decoded correctly
    if (!$conditions) {
        $this->json(['error' => 'Invalid JSON']);
        return;
    }

    $conditions['status'] = 'waiting';
    $conditions['created_at'] = $this->now();

    // 4. Ensure $this->tbl is set (it is, but double check your DB save method)
    $id = $this->db->save($this->tbl, $conditions);

    $data['id'] = $id;
    $data = array_merge($data, $conditions);

    $this->json($data);
}

        public function old_test($post = ''){
                $data =array('id'=>'');

                $conditions = json_decode($post,true);

                $conditions['status'] = 'waiting';

                $conditions['created_at'] = $this->now();

                $id = $this->db->save($this->tbl,$conditions);

                $data['id'] = $id;

                $data = $data + $conditions;

                //TODO: Create jobs for Agents
        //        $data = $this->job($data);

                $this->json($data);

	}

}// end of class
