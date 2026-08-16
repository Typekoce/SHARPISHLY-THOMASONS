<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

class AzureController extends BaseController
{
    public function hello(string $id = ''): void
    {
        $userInput   = $this->request('user') ?? 'Paul';
        $actionInput = $this->request('action') ?? 'test';

        $data = [
            'id' => $id,
        ];

        try {
            $conditions = [
                // TODO: Entry needs to be added to Service/Orm.php
                'source' => 'AzureHelloWorld',
                'method' => 'POST',
                'data'   => [
                    'user'   => $userInput,
                    'action' => $actionInput,
                    'id'     => $id,
                ],
            ];

            $response = $this->orm->execute($conditions);

            $data[__FUNCTION__] = $response;
            $this->json(['status' => 'success', 'data' => $data]);
        } catch (Throwable $e) {
            $this->logger->error("AwsController Error: " . $e->getMessage());
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}