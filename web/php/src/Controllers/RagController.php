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

    public function rag($chat = '') {
        // 1. Properly encode the query to handle spaces/symbols
        $encodedQuery = urlencode($chat);
        $url = "http://localhost:8765/rag/ask?query=" . $encodedQuery;

        // 2. Fetch data and handle potential failure
        $res = file_get_contents($url);
        
        // 3. Decode the JSON string into an array, or return empty array if failed
        $data = json_decode($res, true) ?? [];

        // 4. Pass the actual array $data, not the non-existent $rs
        $this->json($data);
    }

    /**
     * Handles the chat request from the frontend.
     * * @param string $chat The user query
     */
    public function chat($chat = '') {
        $query = $chat ?: $this->request('query');
        
        if (empty($query)) {
            return $this->json(['status' => 'error', 'message' => 'No query provided'], 400);
        }

        $payload = json_encode(['query' => $query]);
        $response = $this->respond($payload,null,'GET');
        
        if (!$response) {
             return $this->json(['status' => 'error', 'message' => 'Service unreachable'], 500);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON from RAG service'], 502);
        }

        // Defensive DB handling
        try {
            $this->logger->log("DEBUG: Calling query()", 'INFO');
            $this->query($query, $response);
        } catch (\Throwable $e) {
            $this->logger->error("Database write failed: " . $e->getMessage());
            // Return success anyway, as the RAG response was already fetched, 
            // or return an error if DB storage is mandatory.
        }

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
