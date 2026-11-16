<?php
namespace App\Services;

use App\Models\JobListing;
use App\Models\User;

class JobSearchContext
{
    public function fingerprint(User $user): string
    {
        $user->loadMissing(['jobPreference', 'skills', 'projects', 'certifications', 'assessmentAttempts.assessment']);
        return hash('sha256', json_encode([
            app(VerifiedEvidence::class)->terms($user), $user->programme,
            $user->jobPreference?->only(['preferred_fields', 'preferred_locations', 'work_types', 'minimum_salary', 'maximum_salary', 'accepts_remote']),
            $user->projects->where('status', 'verified')->map->only(['id', 'title', 'updated_at'])->all(),
            $user->certifications->where('status', 'verified')->map->only(['id', 'title', 'expiry_date', 'updated_at'])->all(),
        ]));
    }

    public function visible(JobListing $listing, User $user): bool
    {
        return $listing->fetched_at?->gte(now()->subDays(14))
            && app(JobCatalogue::class)->eligible($listing)
            && (!$listing->expires_at || $listing->expires_at->isFuture())
            && !in_array($listing->link_status, [404, 410], true)
            && (($user->jobPreference?->accepts_remote ?? true) || ($listing->source !== 'remotive'
                && !preg_match('/remote|worldwide|anywhere|global/i', ($listing->location ?? '').' '.($listing->work_type ?? ''))));
    }
}
