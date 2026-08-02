<?php

namespace App\Models;

class SeedModel extends BaseModel 
{
    private string $agentTable = 'agents';

    /**
     * Seed initial agent records into the agents table with valid execution content
     */
    public function seedAgentModel(): bool 
    {
        // Prevent duplicate seeding on non-empty table
        $existing = $this->db->find([
            'tbl'   => $this->agentTable,
            'limit' => 1
        ]);

        if (!empty($existing)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $seeds = [
            [
                'agent_name'  => 'GMail Integration Agent',
                'role'        => 'career',
                'description' => 'Tailored CV sent to highlight MVC zero-dependency framework and API architecture.',
                'status'      => 'completed',
                'content'     => json_encode([
                    'agent_name' => 'GMail Integration Agent',
                    'category'   => 'career',
                    'steps'      => [
                        [
                            'step'   => 1,
                            'action' => 'send_email',
                            'params' => ['subject' => 'Application Update']
                        ]
                    ]
                ]),
                'created_at'  => $now
            ],
            [
                'agent_name'  => 'Virgin Media Bill Inspector',
                'role'        => 'subscription',
                'description' => 'Retrieved monthly statement. Direct Debit confirmed for 1st of next month.',
                'status'      => 'running',
                'content'     => json_encode([
                    'agent_name' => 'Virgin Media Bill Inspector',
                    'category'   => 'subscription',
                    'steps'      => [
                        [
                            'step'   => 1,
                            'action' => 'check_statement',
                            'params' => ['provider' => 'virgin_media']
                        ]
                    ]
                ]),
                'created_at'  => $now
            ],
            [
                'agent_name'  => 'Manchester City Council Agent',
                'role'        => 'bills',
                'description' => 'Council tax installment retrieved. Payment workflow queued for authorization.',
                'status'      => 'pending',
                'content'     => json_encode([
                    'agent_name' => 'Manchester City Council Agent',
                    'category'   => 'bills',
                    'trigger'    => 'manual',
                    'steps'      => [
                        [
                            'step'   => 1,
                            'action' => 'log_task',
                            'params' => ['message' => 'Council tax payment workflow started']
                        ],
                        [
                            'step'   => 2,
                            'action' => 'authorize_payment',
                            'params' => ['payee' => 'manchester_council']
                        ]
                    ]
                ]),
                'created_at'  => $now
            ],
            [
                'agent_name'  => 'NHS GP Appointment Agent',
                'role'        => 'health',
                'description' => 'Schedule appointment and sync upcoming checkup details to CalDAV store.',
                'status'      => 'pending',
                'content'     => json_encode([
                    'agent_name' => 'NHS GP Appointment Agent',
                    'category'   => 'health',
                    'steps'      => [
                        [
                            'step'   => 1,
                            'action' => 'sync_caldav',
                            'params' => ['event' => 'gp_appointment']
                        ]
                    ]
                ]),
                'created_at'  => $now
            ]
        ];

        foreach ($seeds as $data) {
            $this->db->save($this->agentTable, $data);
        }

        return true;
    }
}