<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * THOMASONS V3 – BaseController
 * Centralized service retrieval and Neural Handshake orchestration.
 */
use App\Services\Db;
use App\Services\Location;
use App\Services\Smarty;
use App\Services\Logger;
use App\Services\Session;
use Throwable;

abstract class BaseController
{
    protected $db;
    protected $loc;
    protected $location;
    protected $smarty;
    public $logger;
    protected $model;
    public $session;

    /**
     * Default Neural Stack for Thomasons V3.
     * Can be overridden in child controllers.
     */
    protected const REQUIRED_MODELS = [
        'llama3.1:latest',
        'nomic-embed-text:latest'
    ];

    public function __construct()
    {
        $this->db       = new \App\Services\Db(); 
        $this->loc      = new \App\Services\Location();
        $this->location = $this->loc;
        $this->smarty   = new \App\Services\Smarty();
        $this->logger   = new \App\Services\Logger();
        $this->session  = \App\Services\Session::getInstance();
    }
    
    /**
     * Standardized JSON Response Handler
     */
    protected function json(array $data, int $code = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($code);
        }
        echo json_encode($data);
        exit;
    }

    /**
     * NEURAL HANDSHAKE: Interrogates the Ollama engine.
     * Centralized here so any controller can verify model "Brain Matter" existence.
     */
    protected function getNeuralStatus(): array
    {
        $status = [
            'active' => false, 
            'synced' => false, 
            'models' => []
        ];
        
        try {
            $ch = curl_init('http://llm:11434/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 1
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $response) {
                $status['active'] = true;
                $data = json_decode($response, true);
                $installed = $data['models'] ?? [];

                $allReady = true;
                foreach (static::REQUIRED_MODELS as $required) {
                    $match = null;
                    foreach ($installed as $m) {
                        if ($m['name'] === $required) {
                            $match = $m;
                            break;
                        }
                    }

                    if ($match && ($match['size'] > 0)) {
                        $gbSize = round($match['size'] / (1024 * 1024 * 1024), 2);
                        $status['models'][$required] = [
                            'size'     => $gbSize . " GB",
                            'progress' => '100%',
                            'state'    => 'Ready'
                        ];
                    } else {
                        $allReady = false;
                        $status['models'][$required] = [
                            'size'     => "0 GB",
                            'progress' => "0% (Missing)",
                            'state'    => 'Critical'
                        ];
                    }
                }
                $status['synced'] = $allReady;
            }
        } catch (Throwable $e) {
            $this->logger->error("Neural Handshake Failed: " . $e->getMessage());
            $status['error'] = "Ollama Offline";
        }
        
        return $status;
    }

    /**
     * Orchestrates Header, Main, and Footer views using the Smarty engine.
     */
    protected function render(array $data, array $views): void
    {
        $output = '';
        foreach ($views as $name => $path) {
            $output .= $this->renderView($path, $data);
        }
        echo $output;
    }

    /**
     * Loads a view file and processes it via the Smarty service.
     */
    protected function renderView(string $path, array $data): string
    {
        $viewPath = $this->loc->baseDir() . "php/views/{$path}.html";
        
        if (!file_exists($viewPath)) {
            $this->logger->error("View not found: " . $viewPath);
            return "";
        }

        $template = file_get_contents($viewPath);
        return $this->smarty->render($template, $data);
    }

    /**
     * Helper for quick variable dumping.
     */
    public function dBug($debug){
        echo "<pre>";
        print_r($debug);
        echo "</pre>";
    }
}