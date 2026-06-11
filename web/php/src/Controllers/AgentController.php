<?php

namespace App\Controllers;

class AgentController extends BaseController {

        public $tbl = 'agents';

        public $auto = "python3 pymvc/app/form_automation.py ";

        public function index(){
                //TODO: Move functionality to AgentModel. Display all agent task
                $conditions = array(
                        'tbl'	=> $this->tbl,
                        'order'	=> array('id'	=> 'DESC'),
                );

                $rs = $this->db->find($conditions);

                $this->json($rs);
        }

        public function fillForm($agentId, $formUrl, $developerId) {
        if (!filter_var($formUrl, FILTER_VALIDATE_URL)) {
                $this->json(['error' => 'Invalid form URL']);
                return;
        }

        try {
                // Fetch developer data using your existing Db service pattern
                //TODO: db->find-one() does not exist use $conditions where instead
                $data = $this->db->find_one('developers', $developerId);
                
                if (!$data) {
                $this->json(['error' => 'Developer not found']);
                return;
                }

                $payload = [
                'target_url' => $formUrl,
                'mode'       => 'draft',
                'field_map'  => [
                        '#name'       => $data['full_name'],
                        '#email'      => $data['email'],
                        '#experience' => $data['years_exp']
                ]
                ];

                // Execute automation
                $result = shell_exec($this->auto . escapeshellarg(json_encode($payload)));
                
                $this->json(['status' => 'success', 'output' => $result]);

        } catch (\Exception $e) {
                $this->json(['error' => $e->getMessage()]);
        }
        }





}// end of class
