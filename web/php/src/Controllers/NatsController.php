<?php

namespace App\Controllers;

class NatsController extends BaseController {
    /**
     * Display items in storage/nats/
     */
    public function index(){
        // List items in Nats folder
        foreach($this->loc->storage('storage/nats/') as $k => $v){

        }
    }

    /**
     * Get last inserted id in jobs table or call in JobController?
     * Add file to storage/nats folder: 001_jobs.json
     */
    public function produce(){

        $conditions = array(
            'tbl'=>'jobs',
            'last'=>'inserted'
        );

        $data = $this->db->find($conditions);

        $this->submit($data);

    }

    /**
     * Write file to the 
     */
    public function submit($data) {
        // Generate a unique or sequential filename
        $filename = '001_jobs.json'; 
        $path = $this->loc->getPath('storage/nats/') . $filename;
        
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}