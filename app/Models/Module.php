<?php

namespace App\Models;

use App\Domain\Catalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['cycle_id', 'level', 'name', 'code'];

    protected $hidden = ['name_key', 'code_key'];

    protected static function booted(): void
    {
        static::saving(function (Module $module) {
            $module->name_key = Catalog::key($module->name);
            $module->code_key = Catalog::key($module->code);
        });
    }

    protected function casts(): array
    {
        return [];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
