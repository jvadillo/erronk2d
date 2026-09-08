<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(Period::class)->orderBy('position');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
