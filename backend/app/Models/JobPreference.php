<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPreference extends Model
{
    protected $fillable = [
        'preferred_fields', 'preferred_locations', 'work_types', 'accepts_remote',
        'minimum_salary', 'maximum_salary',
    ];

    protected function casts(): array
    {
        return [
            'preferred_fields' => 'array',
            'preferred_locations' => 'array',
            'work_types' => 'array',
            'accepts_remote' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
