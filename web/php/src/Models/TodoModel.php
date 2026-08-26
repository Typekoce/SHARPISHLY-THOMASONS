<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class TodoModel extends BaseModel
{
    public $debug = TRUE;
    
    public $data = [];

    public function create()
    {
        if ($this->debug === TRUE) {
            $save = array(
                'title'       => 'AWS Queue',
                'description' => 'Complete proposal to prospective companies',
                'source_id'   => 1,
                'status'      => 'active'
            );

            $this->db->save('todo', $save);
        }

        $rs = $this->read();

        $this->data['result'] = $rs;

        return $this->data;
    }// end

    public function update()
    {
        if ($this->debug === TRUE && $payload === null) {
            $payload = [
                'title'       => 'AWS Queue - Updated',
                'description' => 'Complete proposal to prospective companies',
                'source_id'   => 1,
                'status'      => 'completed'
            ];
        }

        if (!empty($payload)) {
            $conditions = ['id' => $id];
            $this->db->update('todo', $payload, $conditions);
        }

        $this->data['result'] = $this->read($id);

        return $this->data;
    }

    public function read(){

        try {
            $conditions = array(
                'tbl'   => 'todo',
                'order' => array('id' => 'DESC')
            );

            $rs = $this->db->find($conditions);

        } catch (Throwable $e) {
            
            if (isset($this->logger)) {
                $this->logger->error("TodoModel::create failed: " . $e->getMessage());
            }

            $rs = array(
                'error'   => true,
                'message' => $e->getMessage()
            );
        }
        
        return $rs;

    }
}