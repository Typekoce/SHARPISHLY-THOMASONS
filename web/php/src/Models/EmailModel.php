<?php

namespace App\Models;

class EmailModel extends BaseModel {

    /**
     * Generic send email
     */
    public function send(){
        $subject = "Hello world";
        $message = "This developer is amazing";
        $sender = "paul@sharpishly.com";
        $to = "paul+php@sharpishly.com";
        mail($to,$subject,$message);
    }

}
