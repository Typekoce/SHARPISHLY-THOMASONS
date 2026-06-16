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
        $query = !empty($chat) ? $chat : ($this->request('query') ?? '');

        if (empty($query)) {
            return $this->json(['status' => 'error', 'message' => 'No query provided'], 400);
        }

        $payload = json_encode(['query' => $query]);
        
        // FIX: Assign the result of the respond method to $response
        $response = $this->respond($payload);
        
        // Verify we got valid data before calling query() or json_decode()
        if (!$response) {
             return $this->json(['status' => 'error', 'message' => 'Service failed'], 500);
        }

        $this->query($query, $response);

        return $this->json(json_decode($response, true));
    }

    public function respond($payload){
        // 3. Send as POST request (aligns with what your Python service expects)
        $ch = curl_init(self::RAG_SERVICE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 4. Handle service communication errors
        if ($httpCode !== 200) {
            return $this->json([
                'status' => 'error', 
                'message' => 'RAG service unreachable',
                'debug' => $error
            ], 500);
        }

        return $response;
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
