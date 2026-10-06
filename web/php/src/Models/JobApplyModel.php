<?php

namespace App\Models;

class JobApplyModel extends BaseModel {

public function customiseNewCv(array $cv, array $job): array
    {
        $customized = $cv;
        
        // Inject target role and company context into the CV payload
        if (!empty($job)) {
            $customized['tailored_for_company'] = $job['company'] ?? 'Target Company';
            $customized['target_role'] = $job['title'] ?? 'Target Role';
            
            // Pass the job array so we can blend the title intelligently
            $customized = $this->blendedJobtitle($customized, $job);
            
            // Map and deconstruct vacancy elements to tailor the CV profile
            $customized = $this->settingFieldNameMappings($customized, $job);
        }

        return $customized;
    }

    public function blendedJobtitle(array $customized, array $job): array
    {
        $primaryTitle = $customized['title'] ?? '';
        $targetTitle = $job['title'] ?? '';

        if (empty($targetTitle)) {
            return $customized;
        }

        // Avoid messy mashing. If the target title is specific (e.g., Senior Web Designer & Developer)
        // and the primary is architectural, blend them cleanly or prioritize the target alignment 
        // while preserving the candidate's core seniority and discipline.
        
        // Example intelligent mapping rule:
        // If applying for a design/front-end heavy role, bridge systems architecture with the target focus.
        if (stripos($targetTitle, 'Web Designer') !== false || stripos($targetTitle, 'Frontend') !== false) {
            $customized['title'] = 'Systems Architect & ' . $targetTitle;
        } else {
            // Default to matching the target vacancy title closely for recruiter ATS alignment
            $customized['title'] = $targetTitle;
        }

        return $customized;
    }
    
    public function settingFieldNameMappings(array $customized, array $job): array
    {
        $requirements = $job['requirements'] ?? [];
        $candidateSkills = $customized['skills'] ?? [];

        // 1. Explode and normalize candidate skills into lowercase keys for comparison
        $normalizedSkills = array_map(function($skill) {
            return strtolower(trim($skill));
        }, $candidateSkills);

        // 2. Map and match job requirements against candidate skills
        $matchedSkills = [];
        $missingRequirements = [];

        foreach ($requirements as $req) {
            $reqLower = strtolower($req);
            $matched = false;

            // Check if any candidate skill matches words/tokens in the requirement
            foreach ($normalizedSkills as $skill) {
                if (!empty($skill) && str_contains($reqLower, $skill)) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                $matchedSkills[] = $req;
            } else {
                $missingRequirements[] = $req;
            }
        }

        // 3. Construct structured mapping for the tailored profile
        $customized['vacancy_mapping'] = [
            'core_focus' => $job['title'] ?? '',
            'matched_requirements' => $matchedSkills,
            'unmatched_requirements' => $missingRequirements,
            'match_score' => count($requirements) > 0 
                ? round((count($matchedSkills) / count($requirements)) * 100) . '%' 
                : '100%'
        ];

        // 4. Fallback / Placeholder status block
        $customized['ai_customization_status'] = [
            'status' => 'success',
            'mode' => 'php_heavy_lifting_completed',
            'timestamp' => date('c'),
            'note' => 'Skills exploded and mapped successfully in PHP prior to RAG prompt generation.'
        ];

        return $customized;
    }


}