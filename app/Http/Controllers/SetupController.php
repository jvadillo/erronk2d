<?php

namespace App\Http\Controllers;

use App\Domain\Grades\ChallengeWriter;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\Classroom;
use App\Models\GoogleRegistration;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if($request->user()->role === 'student', 403);

        return Inertia::render('Setup', ['years' => AcademicYear::with('periods')->withExists(['periods as periods_locked' => fn ($query) => $query->whereHas('challenges')])->get(), 'classrooms' => Classroom::with('users', 'students', 'modules.teachers')->get(), 'users' => User::with('classroom')->orderBy('name')->get(), 'rubrics' => Rubric::all(), 'permissions' => User::PERMISSIONS, 'registrations' => $request->user()->role === 'admin' ? GoogleRegistration::where('status', 'pending')->orderBy('created_at')->get(['id', 'name', 'email', 'created_at'])->map(fn ($registration) => [...$registration->toArray(), 'review_url' => route('registrations.update', $registration)]) : []]);
    }

    public function store(Request $r, string $entity): RedirectResponse
    {
        $permissions = ['year' => 'manage_academics', 'classroom' => 'manage_academics', 'student' => 'manage_students', 'teacher' => 'manage_teachers', 'module' => 'manage_modules', 'rubric' => 'manage_rubrics'];
        abort_unless(isset($permissions[$entity]) && $r->user()->allows($permissions[$entity]), 403);
        DB::transaction(function () use ($r, $entity) {
            $id = $r->input('id');
            $before = null;
            switch ($entity) {
                case 'year':
                    $data = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('academic_years')->ignore($id)], 'periods' => 'required|array|min:1|max:12', 'periods.*' => 'required|string|max:100|distinct']);
                    $model = $id ? AcademicYear::lockForUpdate()->findOrFail($id) : new AcademicYear;
                    $before = $model->toArray();
                    $periodsChanged = $model->periods()->orderBy('position')->pluck('name')->all() !== $data['periods'];
                    if ($id && $periodsChanged && $model->periods()->whereHas('challenges')->exists()) {
                        throw ValidationException::withMessages(['periods' => 'El curso ya tiene retos. Sus Evaluaciones se conservan para proteger el histórico.']);
                    }
                    $model->fill(['name' => $data['name']])->save();
                    if ($periodsChanged) {
                        $model->periods()->delete();
                        foreach ($data['periods'] as $pos => $name) {
                            $model->periods()->create(['name' => $name, 'position' => $pos + 1]);
                        }
                    }
                    break;
                case 'classroom':
                    $data = $r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('classrooms')->where('academic_year_id', $r->academic_year_id)->ignore($id)], 'academic_year_id' => 'required|exists:academic_years,id', 'user_ids' => 'present|array', 'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])]]);
                    $model = $id ? Classroom::findOrFail($id) : new Classroom;
                    if ($id && $model->academic_year_id !== (int) $data['academic_year_id'] && ($model->challenges()->exists() || $model->students()->exists())) {
                        throw ValidationException::withMessages(['academic_year_id' => 'Una clase con estudiantes o retos conserva su curso académico.']);
                    }
                    $before = $model->toArray();
                    $model->fill(collect($data)->except('user_ids')->all())->save();
                    $removedTeachers = $model->users()->pluck('users.id')->diff($data['user_ids']);
                    $model->users()->detach($removedTeachers);
                    $model->users()->syncWithoutDetaching($data['user_ids']);
                    break;
                case 'student': case 'teacher':
                    $data = $r->validate(['name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)], 'password' => ($id ? 'nullable' : 'required').'|string|min:10|max:200', 'active' => 'required|boolean', 'permissions' => 'sometimes|array', 'permissions.*' => [Rule::in(User::PERMISSIONS)]]);
                    if ($entity === 'student') {
                        $data += $r->validate(['classroom_id' => 'required|integer|exists:classrooms,id'], ['classroom_id.required' => 'Selecciona una clase para el estudiante.', 'classroom_id.exists' => 'La clase seleccionada no existe.']);
                    }
                    $model = $id ? User::where('role', $entity)->lockForUpdate()->findOrFail($id) : new User(['role' => $entity]);
                    $before = $model->only(['id', 'name', 'email', 'role', 'permissions', 'active', 'classroom_id']);
                    if (isset($data['permissions']) && $r->user()->role !== 'admin') {
                        abort(403, 'Solo el administrador puede conceder permisos.');
                    }
                    if (empty($data['password'])) {
                        unset($data['password']);
                    }
                    if ($entity === 'student') {
                        unset($data['permissions']);
                    }
                    if (Str::lower($model->email ?? '') !== Str::lower($data['email'])) {
                        $model->forceFill(['google_id' => null, 'email_verified_at' => null]);
                    }
                    $model->fill($data)->save();
                    break;
                case 'module':
                    $data = $r->validate(['name' => 'required|string|max:150', 'code' => ['required', 'string', 'max:30', Rule::unique('modules')->where('classroom_id', $r->classroom_id)->ignore($id)], 'classroom_id' => 'required|exists:classrooms,id', 'teacher_ids' => 'required|array|min:1', 'teacher_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['teacher', 'admin'])->where('active', true))]]);
                    $model = $id ? Module::findOrFail($id) : new Module;
                    if ($id && $model->classroom_id !== (int) $data['classroom_id']) {
                        abort(422, 'Un módulo existente conserva su clase.');
                    }
                    $before = $model->toArray();
                    $model->fill(collect($data)->except('teacher_ids')->all())->save();
                    $model->teachers()->sync($data['teacher_ids']);
                    $model->classroom->users()->syncWithoutDetaching($data['teacher_ids']);
                    break;
                case 'rubric':
                    $data = $r->validate(['name' => 'required|string|max:150', 'kind' => 'required|in:team,transversal', 'items' => 'required|array|min:1|max:40', 'items.*.key' => 'required|string|max:80|distinct|regex:/^[a-zA-Z0-9_-]+$/', 'items.*.name' => 'required|string|max:150', 'items.*.description' => 'nullable|string|max:2000', 'items.*.module_id' => 'nullable|exists:modules,id', 'items.*.weight' => ['required', 'numeric', 'gt:0', 'max:10000', ChallengeWriter::DECIMAL], 'items.*.levels' => 'required|array|min:2|max:20', 'items.*.levels.*.score' => ['required', 'numeric', 'between:0,10', ChallengeWriter::DECIMAL], 'items.*.levels.*.description' => 'required|string|max:2000']);
                    if ($data['kind'] === 'transversal') {
                        foreach ($data['items'] as &$item) {
                            $item['module_id'] = null;
                        } unset($item);
                    }
                    $model = $id ? Rubric::findOrFail($id) : new Rubric;
                    $before = $model->toArray();
                    $model->fill($data)->save();
                    break;
            }
            AuditEvent::create(['user_id' => $r->user()->id, 'action' => 'setup.'.$entity, 'before' => $before, 'after' => $model->toArray()]);
        });

        return back()->with('success', 'Información guardada.');
    }
}
