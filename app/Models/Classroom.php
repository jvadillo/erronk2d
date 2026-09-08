<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = ['academic_year_id', 'name'];

    protected function casts(): array
    {
        return [];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }
}
