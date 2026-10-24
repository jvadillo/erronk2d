<?php

namespace App\Models;

use App\Domain\Catalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    protected $hidden = ['name_key', 'code_key'];

    protected static function booted(): void
    {
        static::saving(function (Cycle $cycle) {
            $cycle->name_key = Catalog::key($cycle->name);
            $cycle->code_key = Catalog::key($cycle->code);
        });
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }
}
