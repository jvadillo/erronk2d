<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubric extends Model
{
    protected $fillable = ['name', 'kind', 'items'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
