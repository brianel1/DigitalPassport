<?php
namespace App\Services;

use App\Models\JobListing;
use App\Models\User;
use App\Support\MalaysiaLocation;

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
            && $this->inSelectedArea($listing, $user);
    }

    /** Remote roles follow the remote switch; on-site roles must be in a selected state (none selected = all of Malaysia). */
    public function inSelectedArea(JobListing $listing, User $user): bool
    {
        if (MalaysiaLocation::isRemote($listing->location, $listing->work_type, $listing->source)) {
            return (bool) ($user->jobPreference?->accepts_remote ?? false);
        }
        $states = MalaysiaLocation::normalise($user->jobPreference?->preferred_locations ?? []);
        return !$states || in_array(MalaysiaLocation::stateOf($listing->location), $states, true);
    }
}
