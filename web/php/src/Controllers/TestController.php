<?php

namespace App\Controllers;

class TestController extends BaseController {

    /**
     * Test endpoint
     */
    public function test($id = ''){

    $data = array(
        'id'            => $id,
        'class'         => __CLASS__,
        'function'      => __FUNCTION__,
        'google_api'    => $this->decodeJsonRequest('googleapi'),
        'recent_work'   => array(
            'ssl_setup'     => 'setup-local-ssl.sh',
            'installer'     => 'build-installer.sh',
            'controllers'   => array(
                'AzureFoundryController.php',
                'BaseCloudController.php',
                'GoogleapiController.php',
                'HotmailapiController.php',
            ),
            'documentation' => array(
                'docs/CONTRIBUTORS.md',
                'todo.md',
            ),
        ),
    );

        $this->json($data); //[cite: 3]
    }

    /**
     * Decode Json Request
     */
    public function decodeJsonRequest($url){

        $gmailRaw = file_get_contents('http://sharpishly.dev/php/' . $url ); //

        return json_decode($gmailRaw, true);

    }

}