<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class AssessmentEvidence
{
    public function snapshot(Assessment $assessment): array
    {
        $assessment->loadMissing('questions');
        return [
            'title' => $assessment->title, 'category' => $assessment->category, 'description' => $assessment->description,
            'version' => $assessment->version, 'family_id' => $assessment->parent_assessment_id ?? $assessment->id,
            'skill_names' => $assessment->skill_names ?? [], 'time_limit' => $assessment->time_limit_minutes, 'passing_score' => $assessment->passing_score,
            'questions' => $assessment->questions->map(fn ($q) => $q->only(['id', 'question', 'options', 'correct_option', 'points']))->values()->all(),
        ];
    }

    public function best(User $user): Collection
    {
        $user->loadMissing('assessmentAttempts.assessment');
        return $user->assessmentAttempts->filter(fn ($a) => $a->submitted_at && $a->passed && $a->assessment)
            ->sortByDesc(fn ($a) => (float) $a->percentage)->unique(fn ($a) => $a->assessment_snapshot['family_id'] ?? $a->assessment->parent_assessment_id ?? $a->assessment_id)->values();
    }

    public function skills(AssessmentAttempt $attempt): array
    {
        return $attempt->assessment_snapshot['skill_names'] ?? $attempt->assessment?->skill_names ?? [];
    }
}
