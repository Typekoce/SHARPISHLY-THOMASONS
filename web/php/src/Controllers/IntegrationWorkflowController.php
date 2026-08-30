<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\IntegrationWorkflowModel;
use Throwable;

class IntegrationWorkflowController extends BaseController 
{
    protected IntegrationWorkflowModel $workflowModel;

    public function __construct()
    {
        parent::__construct();
        $this->workflowModel = new IntegrationWorkflowModel();
    }    

    public function create(): void
    {
        $payload = $this->request();
        $result  = $this->workflowModel->create($payload);

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
        $result = $this->workflowModel->read($id);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function update(string $id = ''): void
    {
        $payload = $this->request();
        $result  = $this->workflowModel->update($id, $payload);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function delete(string $id = ''): void
    {
        $result = $this->workflowModel->delete($id);
        $this->json(['status' => 'success', 'data' => $result]);
    }

    public function executeAiIngest(string $id = ''): void
    {
        try {
            $neuralStatus = $this->getNeuralStatus();

            $records = $this->workflowModel->read($id);
            if (empty($records) || isset($records['error'])) {
                $this->json(['status' => 'error', 'message' => 'Workflow record not found'], 404);
                return;
            }

            $updateResult = $this->workflowModel->update($id, [
                'status'  => 'completed',
                'payload' => json_encode([
                    'processed_by' => 'EmbraceCare-AI-Pipeline',
                    'neural_ready' => $neuralStatus['synced'] ?? false,
                    'timestamp'    => $this->now()
                ])
            ]);

            $this->json([
                'status'        => 'success',
                'neural_status' => $neuralStatus,
                'data'          => $updateResult
            ]);
        } catch (Throwable $e) {
            $this->logger->error("ExecuteAiIngest Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
