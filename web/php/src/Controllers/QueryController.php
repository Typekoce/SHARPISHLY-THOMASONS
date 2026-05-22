<?php

namespace App\Controllers;

class QueryController extends BaseController {

	public function index(){
		$conditions = array(
			'tbl'	=> 'queries',
			'order' => array('id'=>'DESC')
		);

		$rs = $this->db->find($conditions);

		$this->json($rs);

	}

}// end of class
