<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = ['challenge_id', 'kind', 'subject_id', 'scope_id', 'criterion', 'level', 'updated_by'];

    protected function casts(): array
    {
        return [];
    }
}
