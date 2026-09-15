<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['academic_year_id', 'name', 'cycle_id', 'level', 'cycle_name', 'owner_id'];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->active) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->role === 'admin') {
            return $query;
        }
        if ($user->role === 'student') {
            return $query->whereHas('enrollments', fn (Builder $enrollments) => $enrollments->where('student_id', $user->id));
        }

        return $query->where(fn (Builder $members) => $members->where('owner_id', $user->id)->orWhereHas('users', fn (Builder $teachers) => $teachers->where('users.id', $user->id)));
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

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
        return $this->belongsToMany(User::class)->whereIn('role', ['teacher', 'admin']);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'classroom_id', 'student_id')->where('role', 'student')->wherePivotNull('ended_at');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(Period::class)->orderBy('position');
    }

    public function modules(): BelongsToMany
    {
        return $this->catalogModules()->wherePivotNull('ended_at');
    }

    public function catalogModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'classroom_module')->withPivot('name', 'code', 'ended_at');
    }

    public function syncCatalog(): void
    {
        $modules = Module::where('cycle_id', $this->cycle_id)->where('level', $this->level)->get();
        $existing = $this->catalogModules()->pluck('modules.id');
        foreach ($modules as $module) {
            if (! $existing->contains($module->id)) {
                $this->modules()->attach($module->id, ['name' => $module->name, 'code' => $module->code]);
            }
        }
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }
}
