<?php

namespace App\Controllers;

class SocialsController extends BaseController {

    public function index(){

        $data = array(
            'google'    => $this->content(),
            'tiktok'    => $this->content(),
            'instagram' => $this->content()          
        );

        $this->json($data);
    }

    public function content(){
        return array(
            'email'  => array(),
            'posts'  => array(),
            'videos' => array()
        );
    }

}// end of class