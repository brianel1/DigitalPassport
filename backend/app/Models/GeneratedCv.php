<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedCv extends Model
{
    protected $fillable = ['user_id', 'target_job_listing_id', 'title', 'content', 'version', 'status', 'model_name'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetJob(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'target_job_listing_id');
    }
}
