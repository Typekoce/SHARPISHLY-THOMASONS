<?php

namespace App\Controllers;

class FormAutomationController {
    // Stage 1: Prep the form
    public function submitDraft($data) {
        $jobId = $this->db->createJob($data, 'pending_review');
        // Notify Python to launch Playwright in 'draft' mode
        $this->messenger->publish('form.automation.task', ['job_id' => $jobId, 'mode' => 'draft']);
        return ['status' => 'draft_created', 'job_id' => $jobId];
    }

    // Stage 2: Final approval
    public function approveJob($jobId) {
        $this->db->updateStatus($jobId, 'approved');
        // Notify Python to perform the final submit click
        $this->messenger->publish('form.automation.task', ['job_id' => $jobId, 'mode' => 'submit']);
        return ['status' => 'submission_triggered'];
    }
}
