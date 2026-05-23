<?php

namespace App\Controllers;

class DocsController extends BaseController {

	public function index(){

		//TODO: Move to DocsModel
		$conditions = array(
			'tbl'	=> 'queries',
			'order' => array('id' => 'DESC')
		);

		$records = $this->db->find($conditions);

		foreach($records as $record){
			$this->dBug($record);
		}

	}

}// end of class

