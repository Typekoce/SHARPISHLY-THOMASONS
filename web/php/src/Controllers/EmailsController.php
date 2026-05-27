<?php

namespace App\Controllers;
//TODO: Migrate functionality to EmailsModel
class EmailsController extends BaseController {

	public function index(){

	}

	public function test(){

		$data =array('id'=>'');

		$conditions = array(
			'tbl'		=> 'emails',
			'email' 	=> 'paul+test@sharpishly.com',
			'message'	=> 'Hello World',
			'created_at'	=> $this->now(),
			'status'	=> 'waiting'
		);

		$id = $this->db->save('emails',$conditions);

		$data['id'] = $id;

		$data = $data + $conditions;

		$this->json($data);

	}

	public function job(){

	}

}// end of class
