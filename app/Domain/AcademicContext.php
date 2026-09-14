<?php

namespace App\Domain;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AcademicContext
{
    public function availableYears(User $user): Builder
    {
        $query = AcademicYear::query();
        if (! $user->active) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->role === 'student') {
            $query->whereHas('classrooms.enrollments', fn (Builder $enrollments) => $enrollments->where('student_id', $user->id));
        } elseif ($user->role === 'teacher') {
            $query->where(fn (Builder $years) => $years->where('is_open', true)->orWhereHas('classrooms', fn (Builder $classes) => $classes->where(fn (Builder $members) => $members->where('owner_id', $user->id)->orWhereHas('users', fn (Builder $teachers) => $teachers->where('users.id', $user->id)))));
        } elseif ($user->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function resolve(Request $request): ?AcademicYear
    {
        $user = $request->user();
        $saved = $request->session()->get('academic_context');
        $selected = is_array($saved) && ($saved['user_id'] ?? null) === $user->id
            ? ($saved['year_id'] ?? null) : $user->last_academic_year_id;
        $year = $selected ? $this->availableYears($user)->find($selected) : null;
        $year ??= $this->availableYears($user)->where('is_open', true)->first();
        $year ??= $this->availableYears($user)->first();
        $this->remember($request, $year);

        return $year;
    }

    public function remember(Request $request, ?AcademicYear $year): void
    {
        $user = $request->user();
        if ($user->last_academic_year_id !== $year?->id) {
            $user->forceFill(['last_academic_year_id' => $year?->id])->save();
        }
        $request->session()->put('academic_context', ['user_id' => $user->id, 'year_id' => $year?->id]);
        $request->attributes->set('academic_year', $year);
    }

    public function year(): ?AcademicYear
    {
        return request()->attributes->get('academic_year');
    }

    public function classrooms(User $user): Builder
    {
        return Classroom::query()->where('academic_year_id', $this->year()?->id ?? 0)->visibleTo($user);
    }

    public function requireWritable(Request $request): AcademicYear
    {
        $year = $this->year();
        abort_unless($year, 403, 'No hay un curso académico disponible. Espera a la administración.');
        abort_unless($year->is_open, 403, 'El curso académico está cerrado y es de solo lectura.');
        $expected = $request->header('X-Academic-Year', $request->input('academic_year_id'));
        abort_unless((string) $expected === (string) $year->id, 409, 'El curso académico ha cambiado. Actualiza la página antes de guardar.');

        return $year;
    }
}
