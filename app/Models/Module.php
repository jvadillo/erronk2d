<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['cycle_id', 'level', 'name', 'code'];

    protected function casts(): array
    {
        return [];
    }

    public function teachersFor(int $classroomId): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_module_user')->wherePivot('classroom_id', $classroomId)->where('users.active', true);
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
