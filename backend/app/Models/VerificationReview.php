<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VerificationReview extends Model
{
    protected $fillable = ['reviewer_id', 'decision', 'remarks', 'record_snapshot'];

    protected function casts(): array { return ['record_snapshot' => 'array']; }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
