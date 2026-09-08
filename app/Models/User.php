<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'active', 'classroom_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected $attributes = ['active' => true, 'role' => 'student', 'permissions' => '[]'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'active' => 'boolean',
        ];
    }

    public const PERMISSIONS = ['manage_academics', 'manage_students', 'manage_teachers', 'manage_modules', 'manage_teams', 'manage_challenges', 'manage_rubrics', 'evaluate_team', 'evaluate_transversal', 'enter_exams', 'enter_defenses', 'modify_grades', 'publish_results'];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function allows(string $permission): bool
    {
        return $this->active && ($this->role === 'admin' || ($this->role === 'teacher' && in_array($permission, $this->permissions ?? [], true)));
    }

    public function teaches(int $moduleId): bool
    {
        return $this->role === 'admin' || DB::table('module_user')->where('module_id', $moduleId)->where('user_id', $this->id)->exists();
    }
}
