<?php

namespace App\Models;

class JobApplyModel extends BaseModel {

    /**
     * Lightweight synonym map for semantic skill matching.
     * Keys are normalized requirement tokens; values are candidate skill variants.
     */
    private const SKILL_SYNONYMS = [
        'mvc' => ['mvc', 'mvc patterns', 'mvc architectures', 'mvc architecture', 'model-view-controller'],
        'php' => ['php', 'php development', 'php frameworks'],
        'linux' => ['linux', 'linux systems', 'linux administration'],
        'javascript' => ['javascript', 'js', 'vanilla js', 'frontend javascript'],
        'frontend' => ['frontend', 'front-end', 'front end', 'web designer', 'ui'],
        'architecture' => ['architecture', 'systems architect', 'system architecture', 'software architecture'],
        'api' => ['api', 'rest api', 'restful', 'apis'],
        'database' => ['database', 'mysql', 'mariadb', 'sql', 'pdo'],
        'docker' => ['docker', 'containers', 'containerization'],
        'python' => ['python', 'python development'],
    ];

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

            // Reorder skills so vacancy-mentioned ones surface first
            $customized = $this->reorderSkillsByVacancy($customized, $job);

            // Elevate experience highlights that match vacancy requirements
            $customized = $this->reorderExperienceHighlights($customized);

            // Build a structured cover-letter hook for the RAG layer
            $customized = $this->assembleCoverLetterContext($customized, $job);
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

        // 2. Map and match job requirements against candidate skills (with synonym expansion)
        $matchedSkills = [];
        $missingRequirements = [];

        foreach ($requirements as $req) {
            $reqLower = strtolower($req);
            $matched = false;

            // Direct token inclusion check
            foreach ($normalizedSkills as $skill) {
                if (!empty($skill) && str_contains($reqLower, $skill)) {
                    $matched = true;
                    break;
                }
            }

            // Semantic synonym check when direct match fails
            if (!$matched) {
                $matched = $this->matchesViaSynonym($reqLower, $normalizedSkills);
            }

            if ($matched) {
                $matchedSkills[] = $req;
            } else {
                $missingRequirements[] = $req;
            }
        }

        // 3. Construct structured mapping for the tailored profile
        $matchScore = count($requirements) > 0 
            ? round((count($matchedSkills) / count($requirements)) * 100) . '%' 
            : '100%';

        $customized['vacancy_mapping'] = [
            'core_focus' => $job['title'] ?? '',
            'matched_requirements' => $matchedSkills,
            'unmatched_requirements' => $missingRequirements,
            'match_score' => $matchScore,
            // Explicit gap-bridging payload for the RAG prompt
            'gap_bridging_instructions' => $this->buildGapBridgingInstructions($missingRequirements)
        ];

        // 4. Fallback / Placeholder status block
        $customized['ai_customization_status'] = [
            'status' => 'success',
            'mode' => 'php_heavy_lifting_completed',
            'timestamp' => date('c'),
            'note' => 'Skills exploded, synonym-mapped, and vacancy gaps flagged in PHP prior to RAG prompt generation.'
        ];

        return $customized;
    }

    /**
     * Check whether a requirement matches any candidate skill via the synonym table.
     */
    private function matchesViaSynonym(string $reqLower, array $normalizedSkills): bool
    {
        foreach (self::SKILL_SYNONYMS as $canonical => $variants) {
            // Does the requirement contain the canonical term or any of its variants?
            $reqHitsCanonical = str_contains($reqLower, $canonical);
            $reqHitsVariant = false;
            foreach ($variants as $variant) {
                if (str_contains($reqLower, $variant)) {
                    $reqHitsVariant = true;
                    break;
                }
            }

            if (!$reqHitsCanonical && !$reqHitsVariant) {
                continue;
            }

            // Does the candidate possess any of the synonymous skills?
            foreach ($normalizedSkills as $skill) {
                if (in_array($skill, $variants, true) || $skill === $canonical) {
                    return true;
                }
                // Also accept partial containment (e.g. "mvc architectures" contains "mvc")
                foreach ($variants as $variant) {
                    if (str_contains($skill, $variant) || str_contains($variant, $skill)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Build a short instruction string the RAG layer can use to address unmatched requirements.
     */
    private function buildGapBridgingInstructions(array $missingRequirements): string
    {
        if (empty($missingRequirements)) {
            return 'All listed requirements are covered by existing skills; reinforce strongest matches.';
        }

        $list = implode('; ', $missingRequirements);
        return "Address these unmatched requirements by bridging from adjacent experience or transferable skills: {$list}. Do not invent experience; only reframe existing highlights.";
    }

    /**
     * Re-order the skills array so that skills appearing in the vacancy requirements
     * (or their synonyms) appear first. Preserves relative order within each group.
     */
    private function reorderSkillsByVacancy(array $customized, array $job): array
    {
        $skills = $customized['skills'] ?? [];
        if (empty($skills)) {
            return $customized;
        }

        $requirements = $job['requirements'] ?? [];
        $reqText = strtolower(implode(' ', $requirements) . ' ' . ($job['title'] ?? ''));

        $priority = [];
        $remainder = [];

        foreach ($skills as $skill) {
            $skillLower = strtolower(trim($skill));
            $isPriority = false;

            // Direct presence in requirements text
            if (str_contains($reqText, $skillLower)) {
                $isPriority = true;
            } else {
                // Synonym presence
                foreach (self::SKILL_SYNONYMS as $canonical => $variants) {
                    if (in_array($skillLower, $variants, true) || $skillLower === $canonical) {
                        if (str_contains($reqText, $canonical)) {
                            $isPriority = true;
                            break;
                        }
                        foreach ($variants as $v) {
                            if (str_contains($reqText, $v)) {
                                $isPriority = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($isPriority) {
                $priority[] = $skill;
            } else {
                $remainder[] = $skill;
            }
        }

        $customized['skills'] = array_merge($priority, $remainder);
        return $customized;
    }

    /**
     * Elevate experience highlights that contain keywords from matched_requirements.
     * Expects an optional 'experience' or 'highlights' array of strings.
     */
    private function reorderExperienceHighlights(array $customized): array
    {
        $mapping = $customized['vacancy_mapping'] ?? [];
        $matched = $mapping['matched_requirements'] ?? [];
        if (empty($matched)) {
            return $customized;
        }

        $matchedText = strtolower(implode(' ', $matched));

        // Support both common key names without forcing a schema change
        foreach (['experience', 'highlights', 'experience_highlights'] as $key) {
            if (empty($customized[$key]) || !is_array($customized[$key])) {
                continue;
            }

            $priority = [];
            $remainder = [];

            foreach ($customized[$key] as $item) {
                $itemText = is_string($item) ? $item : (is_array($item) ? json_encode($item) : '');
                $itemLower = strtolower($itemText);

                $hits = false;
                foreach ($matched as $req) {
                    $tokens = preg_split('/\s+/', strtolower($req), -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($tokens as $token) {
                        if (strlen($token) > 3 && str_contains($itemLower, $token)) {
                            $hits = true;
                            break 2;
                        }
                    }
                }

                if ($hits) {
                    $priority[] = $item;
                } else {
                    $remainder[] = $item;
                }
            }

            $customized[$key] = array_merge($priority, $remainder);
        }

        return $customized;
    }

    /**
     * Assemble a ready-to-expand cover-letter context block for the RAG service.
     */
    private function assembleCoverLetterContext(array $customized, array $job): array
    {
        $company = $customized['tailored_for_company'] ?? ($job['company'] ?? 'the company');
        $role = $customized['target_role'] ?? ($job['title'] ?? 'the role');
        $score = $customized['vacancy_mapping']['match_score'] ?? 'N/A';
        $matched = $customized['vacancy_mapping']['matched_requirements'] ?? [];
        $gaps = $customized['vacancy_mapping']['unmatched_requirements'] ?? [];

        $matchedSnippet = !empty($matched)
            ? 'Key alignments include: ' . implode(', ', array_slice($matched, 0, 4)) . '.'
            : '';

        $gapSnippet = !empty($gaps)
            ? ' Areas for narrative bridging: ' . implode(', ', array_slice($gaps, 0, 3)) . '.'
            : '';

        $customized['cover_letter_context'] = [
            'hook' => "Application for {$role} at {$company} (skills match score: {$score}).",
            'alignment_summary' => trim($matchedSnippet . $gapSnippet),
            'instruction' => 'Expand the hook into a concise professional opening paragraph. Reinforce matched skills with concrete evidence from the CV; address any gaps only by reframing adjacent experience. Keep tone confident and specific to the vacancy.',
            'target_company' => $company,
            'target_role' => $role,
            'match_score' => $score
        ];

        return $customized;
    }
}