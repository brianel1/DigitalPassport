<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = ['question_id', 'selected_option', 'is_correct', 'points_awarded'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
