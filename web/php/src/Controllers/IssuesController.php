<?php

namespace App\Controllers;

class IssuesController extends BaseController {
    /**
     * Loads issues initially from docs/md/todo.md
     */
    public function index($id = ''){

        $issues = array(
            array('id' => 1, 'title' => 'foo'),
            array('id' => 2, 'title' => 'bar')
        );

        $data = array(
            'id'        => $id,
            'issues'    => $issues
        );

        $this->json($data);

    }
}