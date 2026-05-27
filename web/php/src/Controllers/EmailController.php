<?php

namespace App\Controllers;

class EmailController extends BaseController {

	public function index($id = ''){

		if(file_exists($file = $this->loc->storage('agents/emails/waiting/job_' . $id . '.json'))){

			$job = json_decode(file_get_contents($file));

			$this->dBug($job);

		} else {
			echo $file . " does not exists";
		}

	}

}
