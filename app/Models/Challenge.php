<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = ['classroom_id', 'period_id', 'name', 'description', 'notes', 'starts_at', 'ends_at', 'status', 'weight', 'distribution_enabled', 'clamp_grade', 'component_weights', 'transversal_weights', 'team_rubric', 'transversal_rubric', 'revision', 'catalog_snapshot'];

    protected function casts(): array
    {
        return ['catalog_snapshot' => 'array', 'distribution_enabled' => 'boolean', 'clamp_grade' => 'boolean', 'component_weights' => 'array', 'transversal_weights' => 'array', 'team_rubric' => 'array', 'transversal_rubric' => 'array', 'weight' => 'decimal:4'];
    }

    /**
     * Un profesor solo ve los retos de sus grupos en los que participa algún módulo del que es responsable.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $query->whereIn('challenges.classroom_id', Classroom::visibleTo($user)->select('classrooms.id'));
        if ($user->role !== 'teacher') {
            return $query;
        }

        return $query->whereExists(fn ($responsibilities) => $responsibilities->from('challenge_module')
            ->join('classroom_module_user', fn ($join) => $join->on('classroom_module_user.module_id', '=', 'challenge_module.module_id')->on('classroom_module_user.classroom_id', '=', 'challenges.classroom_id'))
            ->whereColumn('challenge_module.challenge_id', 'challenges.id')
            ->where('classroom_module_user.user_id', $user->id));
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)->withPivot('defense_enabled');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function moduleGrades(): HasMany
    {
        return $this->hasMany(ModuleGrade::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ChallengeEvidence::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'challenge_student')->orderBy('name');
    }
}
