<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NotificationsModel;

class NotificationsController extends BaseController 
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new NotificationsModel();
    }    

    public function aggregate(): void
    {
        $source = $this->request('source') ?? 'GoogleCal';
        
        $externalData = $this->model->fetchExternalNotifications($source);

        if (isset($externalData['error'])) {
            $this->json([
                'status'  => 'error',
                'message' => $externalData['message'],
                'flash'   => [
                    'type'    => 'danger',
                    'message' => $externalData['message']
                ]
            ], 400);
            return;
        }

        $record = $this->model->createFromAggregation($source, $externalData['data']);

        $this->json([
            'status' => 'success',
            'data'   => $record,
            'flash'  => [
                'type'    => 'info',
                'message' => "Successfully aggregated notifications from {$source}"
            ]
        ]);
    }

    public function create(): void
    {
        $payload = $this->request();
        $result  = $this->model->create($payload);

        if (isset($result['error'])) {
            $this->json([
                'status'  => 'error',
                'message' => $result['message'],
                'flash'   => [
                    'type'    => 'danger',
                    'message' => $result['message']
                ]
            ], 400);
            return;
        }

        $this->json([
            'status' => 'success',
            'data'   => $result,
            'flash'  => [
                'type'    => 'success',
                'message' => 'Notification created successfully'
            ]
        ]);
    }

    public function read(string $id = ''): void
    {
        $result = $this->model->read($id);
        $this->json([
            'status' => 'success', 
            'data'   => $result
        ]);
    }

    public function update(string $id = ''): void
    {
        $payload = $this->request();
        $result  = $this->model->update($id, $payload);

        if (isset($result['error'])) {
            $this->json([
                'status'  => 'error',
                'message' => $result['message'],
                'flash'   => [
                    'type'    => 'danger',
                    'message' => $result['message']
                ]
            ], 400);
            return;
        }

        $this->json([
            'status' => 'success', 
            'data'   => $result,
            'flash'  => [
                'type'    => 'success',
                'message' => 'Notification updated successfully'
            ]
        ]);
    }

    public function delete(string $id = ''): void
    {
        $result = $this->model->delete($id);

        if (isset($result['error'])) {
            $this->json([
                'status'  => 'error',
                'message' => $result['message'],
                'flash'   => [
                    'type'    => 'danger',
                    'message' => $result['message']
                ]
            ], 400);
            return;
        }

        $this->json([
            'status' => 'success', 
            'data'   => $result,
            'flash'  => [
                'type'    => 'warning',
                'message' => 'Notification deleted successfully'
            ]
        ]);
    }
}