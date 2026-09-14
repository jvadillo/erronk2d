<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rubric extends Model
{
    protected $fillable = ['name', 'kind', 'items', 'owner_id', 'cycle_id', 'level'];

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
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
