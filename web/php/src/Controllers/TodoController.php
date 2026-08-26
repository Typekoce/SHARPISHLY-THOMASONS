<?php

namespace App\Controllers;

use App\Models\TodoModel;

class TodoController extends BaseController {


    public function __construct()
    {
        parent::__construct();

        $this->model = new TodoModel();
    }    

    public function create(){

        $data = array(
            'function'  => __FUNCTION__,
            'model'     => __CLASS__,
            'method'    => __METHOD__
        );

        $rs = $this->model->create();

        $data['response'] = $rs;
        
        $this->json($data);
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