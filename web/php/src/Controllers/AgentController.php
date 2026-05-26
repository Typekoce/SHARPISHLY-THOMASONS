<?php

namespace App\Controllers;

class AgentController extends BaseController {

	public function index(){
		//TODO: Move functionality to AgentModel. Display all agent task
		$conditions = array(
			'tbl'	=> 'agents',
			'order'	=> array('id'	=> 'DESC'),
		);

		$rs = $this->db->find($conditions);

		$this->json($rs);
	}

}// end of class
