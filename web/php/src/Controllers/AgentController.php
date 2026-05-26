<?php

namespace App\Controllers;

class AgentController extends BaseController {

	public $tbl = 'agent';

	public function index(){
		//TODO: Move functionality to AgentModel. Display all agent task
		$conditions = array(
			'tbl'	=> $this->tbl,
			'order'	=> array('id'	=> 'DESC'),
		);

		$rs = $this->db->find($conditions);

		$this->json($rs);
	}

	public function save(){
		
	   $data = [];

	    $conditions = array(
                'title'     => 'Business Funding',
                'message' => 'List all business funding sources',
                'created_at'=> date('Y-m-d H:i:s')
            );

	    $data['id'] = $this->db->save($this->tbl, $conditions);

	    $this->json($data);

	}

}// end of class
