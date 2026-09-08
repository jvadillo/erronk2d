<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'position'];

    protected function casts(): array
    {
        return [];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }
}
