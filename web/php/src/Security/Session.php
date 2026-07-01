<?php

namespace App\Security;

class Session {
    public function __construct(){

        if(!isset($_SESSION['security'])){

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1); // Ensure this runs over HTTPS
            ini_set('session.use_strict_mode', 1);
            session_start();

            $_SESSION['security'] = TRUE;

        }

    }
}