<?php

namespace App\Controllers;

class TodoController extends BaseController {

    public $data = array('foo' => 'bar');


    public function create(){

        $this->json($this->data);

    }

    public function read(){

        $this->json($this->data);
        
    }

    public function update(){

        $this->json($this->data);
        
    }

    public function delete(){

        $this->json($this->data);
        
    }

}