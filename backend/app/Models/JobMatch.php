<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    protected $fillable = [
        'recommendation_run_id', 'job_listing_id', 'match_score', 'matched_skills',
        'missing_skills', 'explanation', 'rank', 'score_breakdown',
    ];

    protected function casts(): array
    {
        return ['match_score' => 'decimal:2', 'matched_skills' => 'array', 'missing_skills' => 'array', 'score_breakdown' => 'array'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }
}
