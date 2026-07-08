<?php

namespace App\Controllers;

use App\Models\CvAuditModel;

/**
 * CvController
 * 
 * Optimized controller for CV tailoring tasks, maintaining clean separation
 * between execution and audit logging.
 */
class CvController extends BaseController {

    public function index(): void {
        $cvPath = $this->locate('storage/cv/templates/original-cv.pdf');
        $vacancyPath = $this->locate('storage/cv/vacancies/mobile-app-developer');

        $result = $this->tailor($cvPath, $vacancyPath);

        // Separate audit concern from orchestration
        if ($result['status'] === 'success') {
            (new CvAuditModel())->logTailoring($vacancyPath, $result);
        }

        $this->json($result);
    }

    /**
     * Deterministic tailoring method.
     * Returns an array, allowing the caller (index) to handle logging and response.
     */
    public function tailor(string $cvPath, string $vacancyPath): array {
        if (!is_file($cvPath) || !is_file($vacancyPath)) {
            return ['status' => 'error', 'message' => 'Files not found'];
        }

        $cvContent = file_get_contents($cvPath);
        $vacancyContent = file_get_contents($vacancyPath);

        if ($cvContent === false || $vacancyContent === false) {
            return ['status' => 'error', 'message' => 'Unable to read input files'];
        }

        $payload = json_encode([
            'task' => 'tailor_cv',
            'cv_data' => base64_encode($cvContent),
            'vacancy_data' => $vacancyContent,
        ]);

        if ($payload === false) {
            return ['status' => 'error', 'message' => 'Unable to encode payload'];
        }

        $response = $this->respond($payload, null, 'POST');

        if (!$response) {
            return ['status' => 'error', 'message' => 'Tailoring service unreachable'];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return ['status' => 'error', 'message' => 'Invalid tailoring response'];
        }

        return $data;
    }
}