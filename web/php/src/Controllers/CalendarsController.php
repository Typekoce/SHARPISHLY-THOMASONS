<?php

namespace App\Controllers;
//TODO: Migrate functionality to EmailsModel
class CalendarsController extends BaseController {

	public $tbl = 'calendars';

	public function index(){

	}

	public function test(){

		$data =array('id'=>'');

		$conditions = array(
			'tbl'		=> $this->tbl,
			'email' 	=> 'paul+test@sharpishly.com',
			'message'	=> 'Hello World',
			'created_at'	=> $this->now(),
			'status'	=> 'waiting'
		);

		$id = $this->db->save($this->tbl,$conditions);

		$data['id'] = $id;

		$data = $data + $conditions;

		$data = $this->job($data);

		$this->json($data);

	}

	public function job($data){
	 $this->dBug($file = $this->loc->storage('agents/emails/waiting/job_' . $data['id'] . '.json'));
         file_put_contents($file,json_encode($data));
	 die();
	 return $data;
	}

}// end of class
