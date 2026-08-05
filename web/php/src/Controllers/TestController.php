<?php

namespace App\Controllers;

use App\Services\Orm;

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
        'hotmail_api'    => $this->decodeJsonRequest('hotmailapi'),
        'azure_api'    => $this->decodeJsonRequest('azureapi'),
        'aws_api'    => $this->decodeJsonRequest('awsapi'),
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

        // Bind ORM execution results into the data array
        $data = $this->orm($data);

        $this->json($data); //[cite: 3]
    }

    /**
     * Decode Json Request
     */
    public function decodeJsonRequest($url){

        $gmailRaw = file_get_contents('http://sharpishly.dev/php/' . $url ); //

        return json_decode($gmailRaw, true);

    }

    public function orm($data){

        $orm = new Orm();

        $rs = array();

        // 1. ChatGPT call
        $response = $orm->execute([
            'source'  => 'ChatGPT',
            'action'  => 'create',
            'api_key' => 'YOUR_API_KEY',
            'data'    => [
                'model'    => 'gpt-4o',
                'messages' => [['role' => 'user', 'content' => 'Hello!']]
            ]
        ]);

        $rs['ChatGTP']  = $response;

        // 2. Ollama call
        $response = $orm->execute([
            'source' => 'Ollama',
            'action' => 'create',
            'data'   => [
                'model'  => 'llama3',
                'prompt' => 'Explain MVC'
            ]
        ]);

        $rs['Ollama']  = $response;

        $data['orm'] = $rs;

        return $data;
    }

}