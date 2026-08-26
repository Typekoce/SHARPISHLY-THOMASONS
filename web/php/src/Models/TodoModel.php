<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class TodoModel extends BaseModel
{

    public $debug = TRUE;

    public $data;

    public function create(){

        if($this->debug == TRUE){

            $data = array(
                'title'       => 'Sharpishly Tender',
                'description' => 'Complete proposal to prospective companies',
                'source_id' => 1,
                'status'    => 'active'
            );

            $rs = $this->db->save('todo', $data);
        }

        $conditions = array(
            'tbl'   => 'todo',
            'order' => array('id' => 'DESC')
        );

        $rs = $this->db->find($conditions);

        $this->data['result'] = $rs;

        return $this->data;
    }

}