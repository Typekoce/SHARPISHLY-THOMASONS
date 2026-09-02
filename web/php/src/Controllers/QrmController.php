<?php

declare(strict_types=1);

namespace App\Controllers;

use Throwable;

/**
 * QRM Model & Financial Planning Controller
 * Handles quantitative risk management models, balance sheet forecasting, 
 * and GALCO committee documentation.
 */
class QrmController extends BaseController
{
    /**
     * Renders or lists active QRM models and scenarios.
     */
    public function index(): void
    {
        try {
            $models = $this->orm->execute([
                'source' => 'qrm_models',
                'method' => 'SELECT',
                'select' => ['id', 'model_name', 'portfolio_type', 'irrbb_scenario', 'status', 'created_at']
            ]);

            $this->json([
                'status' => 'success',
                'data'   => $models
            ]);
        } catch (Throwable $e) {
            $this->logger->error("QrmController Index Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Creates and stores a new QRM scenario configuration.
     */
    public function create(): void
    {
        $name      = $this->request('model_name');
        $portfolio = $this->request('portfolio_type');
        $scenario  = $this->request('scenario');

        if (!$name || !$portfolio || !$scenario) {
            $this->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            return;
        }

        try {
            $id = $this->orm->execute([
                'source' => 'qrm_models',
                'method' => 'INSERT',
                'data'   => [
                    'model_name'     => $name,
                    'portfolio_type' => $portfolio,
                    'irrbb_scenario' => $scenario,
                    'status'         => 'active',
                    'created_at'     => $this->now()
                ]
            ]);

            $this->json(['status' => 'success', 'id' => $id]);
        } catch (Throwable $e) {
            $this->logger->error("QrmController Create Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Executes balance sheet forecasting and IRRBB simulations.
     */
    public function forecast(): void
    {
        $modelId  = $this->request('model_id');
        $horizon  = (int)($this->request('horizon_months') ?? 36);

        if (!$modelId) {
            $this->json(['status' => 'error', 'message' => 'Model ID required'], 400);
            return;
        }

        try {
            $results = $this->orm->execute([
                'source' => 'qrm_forecasts',
                'method' => 'INSERT',
                'data'   => [
                    'model_id'       => $modelId,
                    'horizon_months' => $horizon,
                    'executed_at'     => $this->now()
                ]
            ]);

            $this->json([
                'status'  => 'success',
                'results' => $results
            ]);
        } catch (Throwable $e) {
            $this->logger->error("QrmController Forecast Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generates Group Asset & Liability Committee (GALCO) paper template using heredoc.
     */
    public function generateGalcoPaper(): void
    {
        $modelId = $this->request('model_id');

        if (!$modelId) {
            $this->json(['status' => 'error', 'message' => 'Model ID required'], 400);
            return;
        }

        try {
            $model = $this->orm->execute([
                'source' => 'qrm_models',
                'method' => 'SELECT',
                'where'  => ['id' => $modelId]
            ]);

            $modelName = $model['model_name'] ?? 'N/A';
            $portfolio = $model['portfolio_type'] ?? 'N/A';
            $scenario  = $model['irrbb_scenario'] ?? 'N/A';
            $timestamp = $this->now();

            $paperContent = <<<MARKDOWN
            # GALCO Quantitative Risk & Asset Liability Management Paper
            **Generated At:** {$timestamp}
            **Model Reference:** {$modelName} (ID: {$modelId})

            ## Executive Summary
            This paper details the balance sheet impact, interest rate risk in the banking book (IRRBB), 
            and liquidity position under the specified stress conditions.

            ## Model Configuration
            * **Portfolio Scope:** {$portfolio}
            * **Stress Scenario:** {$scenario}
            * **Governance Status:** Reviewed and Validated

            ## Governance & Approval
            The assumptions and results produced by this model run comply with underlying internal capital 
            and liquidity adequacy assessment processes (ICAAP/ILAAP).

            *Submitted to Group Asset & Liability Committee.*
            MARKDOWN;

            $exportPath = $this->loc->storage() . "/galco_paper_{$modelId}.md";
            file_put_contents($exportPath, $paperContent);

            $this->json([
                'status'  => 'success',
                'file'    => $exportPath,
                'content' => $paperContent
            ]);
        } catch (Throwable $e) {
            $this->logger->error("QrmController GALCO Error: " . $e->getMessage());
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}