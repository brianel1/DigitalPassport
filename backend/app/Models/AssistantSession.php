<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantSession extends Model
{
    protected $fillable = ['user_id', 'generated_cv_id', 'title', 'mode', 'language', 'messages'];

    protected function casts(): array
    {
        return ['messages' => 'array'];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(GeneratedCv::class, 'generated_cv_id');
    }
}
