<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class TodoModel extends BaseModel
{
    public $debug = TRUE;
    public $data = [];

    public function create(): array
    {
        if ($this->debug === TRUE) {
            $data = array(
                'title'       => 'AWS Queue',
                'description' => 'Complete proposal to prospective companies',
                'source_id'   => 1,
                'status'      => 'active'
            );

            $this->db->save('todo', $data);
        }

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

        $this->data['result'] = $rs;

        return $this->data;
    }
}