<?php

namespace App\Controllers;

class CalendarController extends BaseController {
	
	public $tbl = 'calendars';

	public function index($id = ''){
		
		$data = array('id' => $id);

		$waiting = $this->waiting($id);

		if(isset($waiting[0]['status']) && $waiting[0]['status']==='waiting'){
		//TODO: Additional processing here 
		} else {

			$data['message'] = 'record already processed';
			$this->json($data);

		}

		if(file_exists($file = $this->loc->storage('agents/emails/waiting/job_' . $id . '.json'))){

			$job = json_decode(file_get_contents($file));

			$this->dBug($job);

			$res = mail($job->email,'Subject:test',$job->message);

			$this->db->update($this->tbl, ['status' => 'completed'], ['id' => $id]);
			// Change status of record if sent

			// Move or delete job from agents/emails/waiting folder

		} else {
			echo $file . " does not exists";
		}

	}


	public function waiting($id){

		$conditions = array(
			'tbl'	=> $this->tbl,
			'where'	=> array(
				'id' =>$id,
				'status' => 'waiting'
			)
		);

		return $this->db->find($conditions);
	}
}
