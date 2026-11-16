<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Support\Str;

class JobFitScorer
{
    public function score(JobListing $listing, User $user, ?array $knownEvidence = null): array
    {
        $user->loadMissing('jobPreference');
        $evidence = $knownEvidence ?? app(VerifiedEvidence::class)->terms($user);
        $trusted = collect(array_keys($evidence));
        $requirements = collect(data_get($listing->raw_payload,'requirements_complete',true) ? ($listing->required_skills ?? []) : [])->filter(fn ($v) => is_string($v) && trim($v) !== '')->unique(fn ($v) => $this->normalise($v))->values();
        $matched = $requirements->filter(fn ($v) => $trusted->contains($this->normalise($v)))->values();
        $missing = $requirements->diff($matched)->values();
        $coverage = $requirements->isEmpty() ? 0 : 100 * $matched->count() / $requirements->count();
        $preference = $user->jobPreference;
        $checks = [];
        $compare = function (string $label, array $wanted, ?string $actual, float $weight, bool $exact = false) use (&$checks) {
            if (!$wanted || !filled($actual)) return;
            $actual = $this->normalise($actual);
            $match = collect($wanted)->contains(fn ($value) => $exact ? $this->normalise($value) === $actual : str_contains($actual, $this->normalise($value)));
            $checks[] = ['label' => $label, 'matched' => $match, 'weight' => $weight];
        };
        $locations = $preference?->preferred_locations ?? [];
        $remoteAnywhere = $preference?->accepts_remote && preg_match('/worldwide|anywhere|global|^remote$/i', $listing->location ?? '');
        if ($locations && $remoteAnywhere) $checks[] = ['label' => 'Location / remote preference', 'matched' => true, 'weight' => 8];
        else $compare('Location preference', $locations, $listing->location, 8);
        $compare('Work type', $preference?->work_types ?? [], $listing->work_type, 5, true);
        $compare('Preferred field', $preference?->preferred_fields ?? [], $listing->title, 3);
        $salaryChecked = false;
        if ($preference && ($preference->minimum_salary !== null || $preference->maximum_salary !== null)
            && Str::upper($listing->salary_currency ?? '') === 'MYR' && ($listing->salary_min !== null || $listing->salary_max !== null)) {
            $salaryChecked = true;
            $overlap = !($preference->minimum_salary !== null && $listing->salary_max !== null && $listing->salary_max < $preference->minimum_salary)
                && !($preference->maximum_salary !== null && $listing->salary_min !== null && $listing->salary_min > $preference->maximum_salary);
            $checks[] = ['label' => 'Salary range overlaps your preference', 'matched' => $overlap, 'weight' => 4];
        }
        $available = array_sum(array_column($checks, 'weight'));
        $earned = array_sum(array_column(array_filter($checks, fn ($check) => $check['matched']), 'weight'));
        // Preferences can reduce an evidence-based fit, never create fit without matching skills.
        $factor = $available > 0 ? .8 + .2 * $earned / $available : 1;
        $score = round($coverage * $factor, 2);
        $explanation = $matched->isNotEmpty()
            ? 'This role matches your '.Str::lower($matched->take(3)->join(', ', ', and ')).' skills.'
            : ($requirements->isEmpty() ? 'The listing does not provide recognised skill requirements, so a fit score cannot be assessed.' : 'No verified skill matches yet. Add evidence for relevant skills before relying on this match.');
        return [
            'listing' => $listing, 'score' => $score, 'matched' => $matched->all(), 'missing' => $missing->all(), 'explanation' => $explanation,
            'breakdown' => [
                'version' => 'evidence-fit-v3', 'assessable' => $requirements->isNotEmpty(),
                'evidence' => $matched->mapWithKeys(fn ($name) => [$name => $evidence[$this->normalise($name)] ?? []])->all(),
                'matchedCount' => $matched->count(), 'requirementCount' => $requirements->count(), 'skillCoverage' => round($coverage, 2),
                'preferenceFactor' => round($factor, 3), 'preferences' => $checks, 'salaryCompared' => $salaryChecked,
                'formula' => 'Matched verified requirements ÷ listed requirements × 100 × preference factor (0.8–1.0). With no comparable preferences, the factor is 1.',
                'basis' => 'Current verified skills, verified project technologies, current verified certificates and passed assessments with explicit skill mappings. Uploaded CV claims do not add points.',
                'limits' => 'Requirement-term coverage only. Experience level, work authorisation and employer decisions are not assessed.',
            ],
        ];
    }

    private function normalise(string $value): string
    {
        return app(VerifiedEvidence::class)->normalise($value);
    }
}
