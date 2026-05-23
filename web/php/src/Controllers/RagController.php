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
     * 
     * @param string $chat The user query
     */
    public function chat($chat = '') {
        // If chat parameter is empty, check for query string via GET
        $query = !empty($chat) ? $chat : ($_GET['query'] ?? '');

        if (empty($query)) {
            return $this->json(['status' => 'error', 'message' => 'No query provided'], 400);
        }

	//$this->query($query);

        // Prepare request to the Python microservice
        $url = self::RAG_SERVICE_URL . '?query=' . urlencode($query);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Consistent with service timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Handle service communication errors
        if ($httpCode !== 200) {
            return $this->json([
                'status' => 'error', 
                'message' => 'RAG service unreachable',
                'debug' => $error
            ], 500);
        }

	$this->query($query,$response);

        // Return the JSON response from the Python service
        return $this->json(json_decode($response, true));
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
