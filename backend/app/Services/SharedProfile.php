<?php

namespace App\Services;

use App\Models\User;

class SharedProfile
{
    public const FIELDS = ['programme', 'headline', 'skills', 'projects', 'certifications', 'assessments'];

    public function data(User $user, array $selection): array
    {
        $user->loadMissing(['profile', 'skills', 'projects', 'certifications']);
        $result = ['name' => $user->name, 'sections' => []];
        if (in_array('programme', $selection)) $result['programme'] = $user->programme;
        if (in_array('headline', $selection)) $result['headline'] = $user->profile?->headline;
        $sections = [
            'skills' => fn () => $user->skills->where('status', 'verified')->map(fn ($s) => ['title' => $s->name, 'detail' => $s->proficiency]),
            'projects' => fn () => $user->projects->where('status', 'verified')->map(fn ($p) => ['title' => $p->title, 'detail' => $p->description, 'tags' => $p->technologies ?? []]),
            'certifications' => fn () => $user->certifications->where('status', 'verified')->filter(fn ($c) => !$c->expiry_date || !$c->expiry_date->lt(today()))->map(fn ($c) => ['title' => $c->title, 'detail' => $c->issuing_organization, 'date' => $c->issue_date?->toDateString()]),
            'assessments' => fn () => app(AssessmentEvidence::class)->best($user)->map(fn ($a) => ['title' => $a->assessment_snapshot['title'] ?? $a->assessment->title, 'detail' => 'Best result: '.round($a->percentage).'%']),
        ];
        foreach ($sections as $key => $records) if (in_array($key, $selection)) {
            $result['sections'][] = ['key' => $key, 'title' => ucfirst($key), 'records' => $records()->values()->all()];
        }
        return $result;
    }
}
