<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\User;
use App\Support\MalaysiaLocation;
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
        $states = MalaysiaLocation::normalise($preference?->preferred_locations ?? []);
        if ($states && MalaysiaLocation::isRemote($listing->location, $listing->work_type, $listing->source)) $checks[] = ['label' => 'Location / remote preference', 'matched' => (bool) $preference?->accepts_remote, 'weight' => 8];
        elseif ($states) $checks[] = ['label' => 'Location preference', 'matched' => in_array(MalaysiaLocation::stateOf($listing->location), $states, true), 'weight' => 8];
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
        $level = $this->level($listing);
        $score = round($coverage * $factor * $level['factor'], 2);
        $explanation = $matched->isNotEmpty()
            ? 'This role matches your '.Str::lower($matched->take(3)->join(', ', ', and ')).' skills.'
            : ($requirements->isEmpty() ? 'The listing does not provide recognised skill requirements, so a fit score cannot be assessed.' : 'No verified skill matches yet. Add evidence for relevant skills before relying on this match.');
        return [
            'listing' => $listing, 'score' => $score, 'matched' => $matched->all(), 'missing' => $missing->all(), 'explanation' => $explanation,
            'breakdown' => [
                'version' => 'evidence-fit-v4', 'assessable' => $requirements->isNotEmpty(),
                'evidence' => $matched->mapWithKeys(fn ($name) => [$name => $evidence[$this->normalise($name)] ?? []])->all(),
                'matchedCount' => $matched->count(), 'requirementCount' => $requirements->count(), 'skillCoverage' => round($coverage, 2),
                'preferenceFactor' => round($factor, 3), 'preferences' => $checks, 'salaryCompared' => $salaryChecked,
                'level' => $level['level'], 'levelFactor' => $level['factor'], 'levelNote' => $level['note'],
                'formula' => 'Matched verified requirements ÷ listed requirements × 100 × preference factor (0.8–1.0) × experience-level factor (0.5–1.0). With no comparable preferences, the preference factor is 1.',
                'basis' => 'Current verified skills, verified project technologies, current verified certificates and passed assessments with explicit skill mappings. Uploaded CV claims do not add points.',
                'limits' => 'Requirement-term coverage, adjusted for the seniority stated in the title or required years. Work authorisation and employer decisions are not assessed.',
            ],
        ];
    }

    /** Student accounts target entry roles, so senior titles and long experience requirements reduce the fit. */
    private function level(JobListing $listing): array
    {
        $title = $listing->title ?? '';
        preg_match_all('/\b(\d{1,2})\s*\+?\s*(?:-\s*\d{1,2}\s*)?(?:years?|yrs?|tahun)\b/i', strip_tags($listing->description ?? ''), $years);
        $requiredYears = $years[1] ? min(array_map('intval', $years[1])) : 0;
        [$level, $factor] = match (true) {
            (bool) preg_match('/\b(intern(ship)?|trainee|graduate|fresh|entry[- ]level|junior|jr\.?|latihan industri|pelatih)\b/i', $title) => ['entry', 1.0],
            (bool) preg_match('/\b(principal|staff engineer|lead|head|director|manager|architect|vp|chief)\b/i', $title) => ['lead', .5],
            (bool) preg_match('/\b(senior|sr\.?)\b/i', $title), $requiredYears >= 5 => ['senior', .6],
            (bool) preg_match('/\b(mid|intermediate)\b/i', $title), $requiredYears >= 3 => ['mid', .8],
            default => ['unspecified', 1.0],
        };
        $note = $factor < 1 ? ($requiredYears >= 3 ? "This role asks for about {$requiredYears}+ years of experience, so the fit is reduced." : 'This title indicates a '.($level === 'lead' ? 'lead or senior management' : $level).' role, so the fit is reduced for student-level experience.') : null;
        return ['level' => $level, 'factor' => $factor, 'note' => $note];
    }

    private function normalise(string $value): string
    {
        return app(VerifiedEvidence::class)->normalise($value);
    }
}
