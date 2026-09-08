<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleGrade extends Model
{
    protected $fillable = ['challenge_id', 'module_id', 'student_id', 'exam', 'defense', 'defense_teacher_id', 'defense_date', 'defense_notes', 'updated_by'];

    protected function casts(): array
    {
        return ['exam' => 'decimal:4', 'defense' => 'decimal:4'];
    }
}
