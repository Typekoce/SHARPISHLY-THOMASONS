<?php

namespace App\Controllers;

use App\Models\MailboxModel;

class MailboxController extends BaseController {

    /**
     * Get Inbox from storage/cmd/jobs/completed
     */
    public function index(){

        $data = [];

        $mailbox = new MailboxModel();

        //TODO: Get file from db storage/cmd/jobs/completed/job_1784300597_6a5a4435e63ce.sh.log
        $file = 'cmd/jobs/completed/job_1784300597_6a5a4435e63ce.sh.log';

        $path = $this->loc->storage($file);

        $content = file_get_contents($path);

        $data = $mailbox->parseHimalayaLog($content);

        $this->json($data);

    }
}