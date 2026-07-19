<?php

namespace App\Controllers;

class TestController extends BaseController {

    /**
     * Test endpoint
     */
    public function test($id = ''){
        $gmailRaw = file_get_contents('http://sharpishly.dev/php/googleapi'); //
        
        $data = array(
            'id'        =>  $id, //[cite: 3]
            'class'     => __CLASS__, //[cite: 3]
            'function'  => __FUNCTION__, //[cite: 3]
            'gmail'     => json_decode($gmailRaw, true) // Cleanly parses the nested JSON string
        );

        $this->json($data); //[cite: 3]
    }

}