<?php

namespace App\Controllers;

class TodoController extends BaseController {

    public $data = array('foo' => 'bar');
    public $debug = TRUE;

    public function create(){

        if($this->debug == TRUE){
            $data = array(
                'title'       => 'foo',
                'description' => 'bar',
                'source_id' => 1,
                'status'    => 'active'
            );

            $rs = $this->db->save('todo', $data);
        }

        $conditions = array(
            'tbl'   => 'todo',
            'order' => array('id' => 'DESC')
        );

        $rs = $this->db->find($conditions);

        $this->data['result'] = $rs;
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