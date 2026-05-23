<?php

namespace App\Controllers;

class DocsController extends BaseController {

	public function index(){

		$data = array();

		//TODO: Move to DocsModel
		$conditions = array(
			'tbl'	=> 'queries',
			'order' => array('id' => 'DESC')
		);

		$records = $this->db->find($conditions);

		foreach($records as $record){
			$record['url'] = 'php/docs/pdf/' . $record['id'];
			$content = json_decode($record['content'],TRUE)['answer'];
			$record['answer'] = $content;
			$data['records'][] = $record;
			//$this->dBug($record);
		}
		$this->Json($data);
	}

}// end of class

