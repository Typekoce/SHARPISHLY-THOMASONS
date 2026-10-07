<?php

namespace App\Controllers;

class VacanciesController extends BaseController {

    public function index($id = ''){

        $path = $this->getUrl();

        $this->setLog("NP Step 1: Get URL of vacancies");
        
        $file = file_get_contents($path);

        $this->setLog("NP Step 2: Get contents");

        $this->saveRawHtml($file);

        $this->setLog("NP Step 3: Save raw data to required directory");

        ///echo $file;

    }

    public function setLog($msg){
        $this->logger->log($msg, 'INFO');
    }

    public function getUrl(){

        $url = 'https://www.jobswipe.net/exit/?jobHash=716F0C4F180E1D7DCB11D49BBEAF4258&utm_medium=aggregator&source=CLICKTH-GB&utm_campaign=CLICKTH-GB-JLO&click_id=411c244cd5e29ec8f7cca00e1667e3c78d7dabea-554';

        return $url;

    }

    public function saveRawHtml($file){

        echo $path =$this->location->storage('job_apply/01_webdesigner/raw.html');

        file_put_contents($path,$file);

    }

}