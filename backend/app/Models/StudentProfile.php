<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'headline',
        'bio',
        'phone',
        'location',
        'linkedin_url',
        'github_url',
        'expected_graduation_date',
    ];

    protected function casts(): array
    {
        return ['expected_graduation_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
