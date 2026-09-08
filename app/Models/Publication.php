<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $fillable = ['challenge_id', 'version', 'snapshot', 'published_by'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
