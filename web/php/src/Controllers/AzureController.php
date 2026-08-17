<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

class AzureController extends BaseController
{
    public function hello(): void
    {
        try {
            $response = $this->orm->execute([
                'source' => 'AzureHelloWorld',
                'method' => 'GET',
            ]);

            $this->json(['status' => 'success', 'data' => $response]);
        } catch (Throwable $e) {
            $this->logger->error("AzureController Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}