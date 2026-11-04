<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $fillable = [
        'source', 'external_id', 'title', 'company', 'location', 'work_type',
        'salary_min', 'salary_max', 'salary_currency', 'description', 'required_skills',
        'application_url', 'published_at', 'expires_at', 'fetched_at', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'fetched_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}
