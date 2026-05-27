<?php

namespace App\Controllers;
//TODO: Migrate functionality to EmailsModel
class EmailsController extends BaseController {

	public function index(){

	}

	public function test(){

		$conditions = array(
			'tbl'		=> 'emails',
			'email' 	=> 'paul+test@sharpishly.com',
			'message'	=> 'Hello World',
			'created_at'	=> $this->now(),
			'status'	=> 'waiting'
		);

		$rs = $this->db->save('emails',$conditions);

		$this->json(['id'=>$rs]);

	}

}// end of class
