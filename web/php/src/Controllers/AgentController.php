<?php

namespace App\Controllers;

class AgentController extends BaseController {

    public $tbl = 'agents';
    public $auto = "python3 pymvc/app/form_automation.py ";

    public function index() {
        // Reuse find() pattern
        $conditions = [
            'tbl'   => $this->tbl,
            'order' => ['id' => 'DESC'],
        ];
        $this->json($this->db->find($conditions));
    }

    public function fillForm($agentId, $formUrl, $developerId) {
        // 1. Validate Input
        if (!filter_var($formUrl, FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Invalid form URL']);
        }

        // 2. Fetch developer using existing where pattern
        $conditions = [
            'tbl'   => 'developers',
            'where' => ['id' => $developerId]
        ];
        
        $rs = $this->db->find($conditions);
        $data = !empty($rs) ? $rs[0] : null;

        if (!$data) {
            return $this->json(['error' => 'Developer not found']);
        }

        try {
            // 3. Prepare Payload
            $payload = [
                'target_url' => $formUrl,
                'mode'       => 'draft',
                'field_map'  => [
                    '#name'       => $data['full_name'],
                    '#email'      => $data['email'],
                    '#experience' => $data['years_exp']
                ]
            ];

            // 4. Execute
            $result = shell_exec($this->auto . escapeshellarg(json_encode($payload)));
            
            $this->json(['status' => 'success', 'output' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()]);
        }
    }
}