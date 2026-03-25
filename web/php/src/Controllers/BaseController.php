<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * THOMASONS V3 – BaseController
 * The abstract blueprint for all application controllers.
 * Enforces DRY principles by centralizing service retrieval and response handling.
 */

use App\Registry;
use App\Services\Location;
use App\Services\Smarty;

abstract class BaseController
{
    protected $db;
    protected $loc;
    protected $smarty;

    /**
     * Constructor: Bridges the Controller to the Global Registry.
     * Child controllers MUST call parent::__construct() if they define their own constructor.
     */
    public function __construct()
    {
        // Pull shared instances from the Registry (Populated in bootstrap.php)
        $this->db     = Registry::get('db');
        $this->loc    = Registry::get(Location::class);
        $this->smarty = Registry::get(Smarty::class);

        // Standardize Security Headers for Native App & Web compatibility
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Access-Control-Allow-Origin: *'); 
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
     * Orchestrates Header, Main, and Footer views using the Smarty engine.
     * Use this for Web-facing pages.
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
        // Use the Location service for absolute pathing
        $viewPath = $this->loc->baseDir() . "php/views/{$path}.html";
        
        if (!file_exists($viewPath)) {
            error_log("View not found: " . $viewPath);
            return "";
        }

        $template = file_get_contents($viewPath);
        return $this->smarty->render($template, $data);
    }
}