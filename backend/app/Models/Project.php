<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'title', 'description', 'role', 'technologies', 'project_url', 'repository_url',
        'completion_date', 'status', 'reviewed_by', 'reviewed_at', 'admin_remarks',
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'completion_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'evidenceable');
    }
}
