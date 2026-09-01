<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NotesModel;
use Throwable;

class NotesController extends BaseController 
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new NotesModel();
    }    

    public function create(): void
    {
        $payload = $this->request();
        $result  = $this->model->create($payload);

        $this->json([
            'status'   => 'success',
            'function' => __FUNCTION__,
            'class'    => __CLASS__,
            'method'   => __METHOD__,
            'response' => $result
        ]);
    }

    public function read(string $id = ''): void
    {
        $result = $this->model->read($id);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function details(string $id = ''): void
    {
        if (empty($id)) {
            $this->json(['status' => 'error', 'message' => 'Missing ID for details'], 400);
            return;
        }

        $result = $this->model->getDetails($id);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function update(string $id = ''): void
    {
        $payload = $this->request();
        $result  = $this->model->update($id, $payload);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function delete(string $id = ''): void
    {
        $result = $this->model->delete($id);
        $this->json(['status' => 'success', 'data' => $result]);
    }
}