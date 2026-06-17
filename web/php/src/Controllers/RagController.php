<?php

namespace App\Controllers;

/**
 * RagController
 * 
 * Acts as a bridge between the PHP frontend and the local Python RAG service.
 * Follows the MVC pattern by keeping logic within the service layer (Python).
 */
class RagController extends BaseController {

    // The endpoint where your Python microservice (rag_service.py) is listening
    const RAG_SERVICE_URL = 'http://localhost:8765/rag/ask';

    public function index() {
        // Standard view rendering for the RAG interface
        $this->view('rag_interface');
    }

    /**
     * Handles the chat request from the frontend.
     * * @param string $chat The user query
     */
    public function chat($chat = '') {
        // Use the centralized request helper.
        // It should be responsible for looking in $chat, then $_POST, then php://input.
        $query = $chat ?: $this->request('query');

        if (empty($query)) {
            return $this->json(['status' => 'error', 'message' => 'No query provided'], 400);
        }

        $payload = json_encode(['query' => $query]);
        
        // Respond handles the CURL communication
        $response = $this->respond($payload);
        
        if (!$response) {
             return $this->json(['status' => 'error', 'message' => 'Service unreachable'], 500);
        }

        // Decode here to check validity before logging
        $data = json_decode($response, true);
        if ($data === null) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON from RAG service'], 502);
        }

        $this->query($query, $response);

        return $this->json($data);
    }



    /**
    * Save queries
    *
    **/
    public function query($query = '',$response = ''){

	$this->logger->log($query);

	$conditions = array(
		'title' 	=> 'rag-chat',
		'message'	=> $query,
		'content'	=> $response,
		'created_at'	=> date('Y-m-d H:i:s')
        );

	$this->db->save('queries',$conditions);
    }
}
