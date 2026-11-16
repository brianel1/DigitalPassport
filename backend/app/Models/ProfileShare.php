<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileShare extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['token_hash', 'encrypted_token'];
    protected function casts(): array
    {
        return ['selection' => 'array', 'encrypted_token' => 'encrypted', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
    public function user() { return $this->belongsTo(User::class); }
}
