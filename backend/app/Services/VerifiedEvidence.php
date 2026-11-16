<?php

namespace App\Services;

use App\Models\User;
use App\Services\JobProviders\ExtractsJobSkills;

class VerifiedEvidence
{
    use ExtractsJobSkills;

    public function normalise(string $value): string
    {
        $key = mb_strtolower(trim(preg_replace('/[\s._-]+/u', ' ', $value)));
        return ['js'=>'javascript','vue js'=>'vue','vuejs'=>'vue','react js'=>'react','reactjs'=>'react','node js'=>'node','nodejs'=>'node','postgres'=>'postgresql','amazon web services'=>'aws','fulltime'=>'full time','fullstack'=>'full stack','rest apis'=>'rest api'][$key] ?? $key;
    }

    public function terms(User $user): array
    {
        $user->loadMissing(['skills.projects', 'skills.certifications', 'projects', 'certifications.skills', 'assessmentAttempts.assessment']);
        $terms = [];
        $add = function ($name, $kind, $id) use (&$terms) {
            $key = $this->normalise($name);
            if ($key !== '') $terms[$key][] = ['name' => $name, 'kind' => $kind, 'id' => $id];
        };
        foreach ($user->skills->where('status', 'verified') as $skill) {
            $linked = $skill->projects->isNotEmpty() || $skill->certifications->isNotEmpty();
            $current = $skill->projects->contains('status', 'verified') || $skill->certifications->contains(fn ($c) => $c->status === 'verified' && (!$c->expiry_date || !$c->expiry_date->lt(today())));
            if (!$linked || $current) $add($skill->name, 'verified_skill', $skill->id);
        }
        foreach ($user->projects->where('status', 'verified') as $project) foreach ($project->technologies ?? [] as $name) $add($name, 'verified_project', $project->id);
        foreach ($user->certifications->where('status', 'verified') as $certificate) {
            if ($certificate->expiry_date?->lt(today())) continue;
            foreach ($certificate->skills->pluck('name')->merge($this->extractSkills($certificate->title))->unique() as $name) $add($name, 'current_certificate', $certificate->id);
        }
        foreach (app(AssessmentEvidence::class)->best($user) as $attempt) foreach (app(AssessmentEvidence::class)->skills($attempt) as $name) $add($name, 'passed_assessment', $attempt->id);
        return $terms;
    }

    public function assessedSkill(User $user, string $name): bool
    {
        return app(AssessmentEvidence::class)->best($user)->contains(fn ($a) => collect(app(AssessmentEvidence::class)->skills($a))->contains(fn ($skill) => $this->normalise($skill) === $this->normalise($name)));
    }
}
