<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rubric extends Model
{
    protected $fillable = ['name', 'kind', 'items', 'owner_id', 'cycle_id', 'level'];

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function teamChallenge(): HasOne
    {
        return $this->hasOne(Challenge::class, 'team_rubric_id');
    }

    public function transversalChallenge(): HasOne
    {
        return $this->hasOne(Challenge::class, 'transversal_rubric_id');
    }

    /**
     * The challenge whose rubric this library entry mirrors; it can only be edited from that challenge.
     */
    public function linkedChallenge(): ?Challenge
    {
        return $this->kind === 'team' ? $this->teamChallenge : $this->transversalChallenge;
    }

    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where(fn (Builder $access) => $access->where('owner_id', $user->id)->orWhereHas('sharedUsers', fn (Builder $users) => $users->where('users.id', $user->id)));
    }

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
