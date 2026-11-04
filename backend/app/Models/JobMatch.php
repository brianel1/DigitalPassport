<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    protected $fillable = [
        'recommendation_run_id', 'job_listing_id', 'match_score', 'matched_skills',
        'missing_skills', 'explanation', 'rank',
    ];

    protected function casts(): array
    {
        return ['match_score' => 'decimal:2', 'matched_skills' => 'array', 'missing_skills' => 'array'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
}
