<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRecommendationRun extends Model
{
    protected $fillable = ['user_id', 'profile_snapshot', 'preference_snapshot', 'model_name', 'status', 'failure_reason'];

    protected function casts(): array
    {
        return ['profile_snapshot' => 'array', 'preference_snapshot' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(JobMatch::class, 'recommendation_run_id')->orderBy('rank');
    }
}
