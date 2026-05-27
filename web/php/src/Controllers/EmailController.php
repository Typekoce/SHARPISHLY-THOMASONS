<?php

namespace App\Controllers;

class EmailController extends BaseController {

	public function index($id = ''){

		if(file_exists($file = $this->loc->storage('agents/emails/waiting/job_' . $id . '.json'))){

			$job = json_decode(file_get_contents($file));

			$this->dBug($job);

			$res = mail($job->email,'Subject:test',$job->message);

			$this->dBug($res);

			// Change status of record if sent

			// Move or delete job from agents/emails/waiting folder

		} else {
			echo $file . " does not exists";
		}

	}

}
