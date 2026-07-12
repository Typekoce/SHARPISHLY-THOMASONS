<?php

namespace App\Controllers;

class BashController extends BaseController {

    /**
     * Execute terminals commands from a URL
     * Add to secuirty policy prior release to production
     */
    public function terminal($cmd = ''){

        $data = array(
            'cmd' => $cmd,
            'response' => ''
        );

        if(!empty($cmd)){

            $data['response'] = exec($cmd);

        } else {

        }

        $this->json($data);

    }
}