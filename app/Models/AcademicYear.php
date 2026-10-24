<?php

namespace App\Models;

use App\Domain\Catalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_open'];

    protected $hidden = ['name_key'];

    protected static function booted(): void
    {
        static::saving(fn (AcademicYear $year) => $year->name_key = Catalog::key($year->name));
    }

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
