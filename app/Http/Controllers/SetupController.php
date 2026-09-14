<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Domain\Grades\ChallengeWriter;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Enrollment;
use App\Models\GoogleRegistration;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
    public const SECTIONS = ['courses' => 'Cursos académicos', 'cycles' => 'Ciclos', 'classrooms' => 'Clases', 'teachers' => 'Profesor', 'students' => 'Estudiante', 'modules' => 'Módulos', 'rubrics' => 'Biblioteca de rúbricas', 'registrations' => 'Solicitudes'];

    public const ADMIN_SECTIONS = ['courses', 'cycles', 'teachers', 'modules', 'registrations'];

    public function __construct(private AcademicContext $context) {}

    public function index(Request $request, ?string $section = null): Response|RedirectResponse
    {
        $actor = $request->user();
        abort_if($actor->role === 'student', 403);
        if ($section === null) {
            return redirect()->route('setup.section', ['section' => $actor->role === 'admin' ? 'courses' : 'classrooms']);
        }
        abort_unless(isset(self::SECTIONS[$section]), 404);
        abort_if(in_array($section, self::ADMIN_SECTIONS, true) && $actor->role !== 'admin', 403);
        $classes = $this->context->classrooms($actor)->with('users', 'students', 'modules', 'academicYear.periods')->orderBy('name')->get();
        foreach ($classes as $class) {
            foreach ($class->modules as $module) {
                $module->name = $module->pivot->name;
                $module->code = $module->pivot->code;
                $module->setRelation('teachers', $module->teachersFor($class->id)->get(['users.id', 'users.name']));
            }
            $class->setAttribute('can_manage', $actor->canManageClassroom($class));
        }
        $users = User::query()->where('role', $section === 'teachers' ? 'teacher' : 'student');
        if ($actor->role !== 'admin') {
            $users->whereHas('enrollments', fn (Builder $enrollments) => $enrollments->whereIn('classroom_id', $classes->pluck('id')));
        }
        $people = in_array($section, ['students', 'teachers'], true) ? $users->orderBy('name')->get(['id', 'name', 'email', 'role', 'active']) : collect();
        foreach ($people as $person) {
            $person->setRelation('enrollments', $person->enrollments()->whereIn('classroom_id', $classes->pluck('id'))->get(['id', 'student_id', 'classroom_id', 'ended_at']));
        }

        return Inertia::render('Setup', [
            'section' => $section, 'title' => self::SECTIONS[$section],
            'years' => $section === 'courses' ? AcademicYear::with('periods')->withExists(['periods as periods_locked' => fn (Builder $query) => $query->whereHas('challenges')])->orderByDesc('id')->get() : [],
            'classrooms' => $classes, 'users' => $people,
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->where('active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'cycles' => Cycle::orderBy('name')->get(),
            'modules' => Module::with('cycle')->orderBy('name')->get(),
            'rubrics' => $section === 'rubrics' ? Rubric::availableTo($actor)->with('sharedUsers:id,name')->orderBy('name')->get() : [],
            'permissions' => [],
            'registrations' => $section === 'registrations' ? GoogleRegistration::where('status', 'pending')->orderBy('created_at')->get(['id', 'name', 'email', 'created_at'])->map(fn ($registration) => [...$registration->toArray(), 'review_url' => route('registrations.update', $registration)]) : [],
        ]);
    }

    public function lookupStudent(Request $request): JsonResponse
    {
        abort_unless($request->user()->allows('manage_students'), 403);
        $data = $request->validate(['email' => 'required|email|max:255', 'classroom_id' => 'required|integer']);
        $this->context->classrooms($request->user())->findOrFail($data['classroom_id']);
        $student = User::where('role', 'student')->where('active', true)->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])->first(['id', 'name', 'email']);

        return response()->json(['student' => $student]);
    }

    public function store(Request $request, string $entity): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        abort_unless(in_array($entity, ['year', 'year-status', 'cycle', 'module', 'classroom', 'responsibility', 'student', 'teacher', 'enrollment', 'rubric', 'rubric-copy'], true), 404);
        if (in_array($entity, ['year', 'year-status', 'cycle', 'module', 'teacher'], true)) {
            abort_unless($actor->role === 'admin', 403);
        }
        DB::transaction(function () use ($request, $entity, $actor) {
            if (in_array($entity, ['classroom', 'responsibility', 'enrollment'], true) || ($entity === 'student' && ($actor->role !== 'admin' || $request->filled('classroom_id')))) {
                $this->context->requireWritable($request);
            }
            $request->validate(['id' => 'nullable|integer']);
            $id = $request->integer('id') ?: null;
            $model = match ($entity) {
                'year' => $this->year($request, $id),
                'year-status' => $this->yearStatus($request),
                'cycle' => $this->cycle($request, $id),
                'module' => $this->module($request, $id),
                'classroom' => $this->classroom($request, $id),
                'responsibility' => $this->responsibility($request),
                'enrollment' => $this->enrollment($request),
                'student', 'teacher' => $this->person($request, $entity, $id),
                'rubric', 'rubric-copy' => $this->rubric($request, $entity === 'rubric-copy', $id),
            };
            AuditEvent::create(['user_id' => $actor->id, 'action' => 'setup.'.$entity, 'after' => $model->only(['id', 'name', 'role', 'is_open', 'owner_id', 'classroom_id', 'student_id', 'ended_at'])]);
        });

        return back()->with('success', 'Información guardada.');
    }

    private function year(Request $request, ?int $id): AcademicYear
    {
        $model = $id ? AcademicYear::lockForUpdate()->findOrFail($id) : new AcademicYear;
        abort_if($id && ! $model->is_open, 403, 'Reabre el curso académico antes de editarlo.');
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('academic_years')->ignore($id)], 'periods' => 'required|array|min:1|max:12', 'periods.*' => 'required|string|max:100|distinct']);
        $changed = $model->periods()->pluck('name')->all() !== $data['periods'];
        if ($id && $changed && $model->periods()->whereHas('challenges')->exists()) {
            throw ValidationException::withMessages(['periods' => 'El curso ya tiene retos. Sus Evaluaciones se conservan para proteger el histórico.']);
        }
        $model->fill(['name' => $data['name']])->save();
        if ($changed) {
            $model->periods()->delete();
            foreach ($data['periods'] as $position => $name) {
                $model->periods()->create(['name' => $name, 'position' => $position + 1]);
            }
        }

        return $model;
    }

    private function yearStatus(Request $request): AcademicYear
    {
        $data = $request->validate(['id' => 'required|integer', 'is_open' => 'required|boolean']);
        $model = AcademicYear::lockForUpdate()->findOrFail($data['id']);
        $model->update(['is_open' => $data['is_open']]);

        return $model;
    }

    private function cycle(Request $request, ?int $id): Cycle
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150', Rule::unique('cycles')->ignore($id)], 'code' => ['required', 'string', 'max:30', Rule::unique('cycles')->ignore($id)]]);
        $model = $id ? Cycle::findOrFail($id) : new Cycle;
        $model->fill($data)->save();

        return $model;
    }

    private function module(Request $request, ?int $id): Module
    {
        $model = $id ? Module::findOrFail($id) : new Module;
        $data = $request->validate(['name' => 'required|string|max:150', 'code' => ['required', 'string', 'max:30', Rule::unique('modules')->where('cycle_id', $request->input('cycle_id'))->where('level', $request->input('level'))->ignore($id)], 'cycle_id' => 'required|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4']);
        if ($id && ($model->cycle_id !== (int) $data['cycle_id'] || $model->level !== (int) $data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'Un módulo existente conserva su ciclo y nivel.']);
        }
        $model->fill($data)->save();
        foreach (Classroom::where('cycle_id', $model->cycle_id)->where('level', $model->level)->whereHas('academicYear', fn (Builder $years) => $years->where('is_open', true))->get() as $class) {
            $class->syncCatalog();
        }

        return $model;
    }

    private function classroom(Request $request, ?int $id): Classroom
    {
        $actor = $request->user();
        $year = $this->context->year();
        $model = $id ? $this->context->classrooms($actor)->lockForUpdate()->findOrFail($id) : new Classroom;
        abort_if($id && ! $actor->canManageClassroom($model), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('classrooms')->where('academic_year_id', $year->id)->ignore($id)], 'cycle_id' => 'required|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4', 'user_ids' => 'present|array', 'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)], 'owner_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)]]);
        $ownerId = $id ? $model->owner_id : $actor->id;
        if (isset($data['owner_id']) && (int) $data['owner_id'] !== $ownerId) {
            abort_unless($actor->role === 'admin', 403, 'Solo administración puede transferir la propiedad.');
            $ownerId = (int) $data['owner_id'];
        }
        if ($id && ($model->cycle_id !== (int) $data['cycle_id'] || $model->level !== (int) $data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'La clase conserva su ciclo y nivel. Crea otra clase para cambiarlos.']);
        }
        $model->fill(['name' => $data['name'], 'academic_year_id' => $year->id, 'cycle_id' => $data['cycle_id'], 'level' => $data['level'], 'owner_id' => $ownerId, 'cycle_name' => $id ? $model->cycle_name : Cycle::findOrFail($data['cycle_id'])->name])->save();
        $model->users()->sync(array_unique([...$data['user_ids'], $ownerId]));
        $model->syncCatalog();

        return $model;
    }

    private function responsibility(Request $request): Classroom
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'module_id' => 'required|integer', 'teacher_ids' => 'present|array', 'teacher_ids.*' => 'integer|distinct']);
        $class = $this->context->classrooms($request->user())->findOrFail($data['classroom_id']);
        abort_unless($request->user()->canManageClassroom($class), 403);
        $class->modules()->findOrFail($data['module_id']);
        $members = $class->users()->where('users.active', true)->pluck('users.id');
        if (collect($data['teacher_ids'])->diff($members)->isNotEmpty()) {
            throw ValidationException::withMessages(['teacher_ids' => 'Los responsables deben ser profesores activos de esta clase.']);
        }
        DB::table('classroom_module_user')->where('classroom_id', $class->id)->where('module_id', $data['module_id'])->delete();
        foreach ($data['teacher_ids'] as $teacherId) {
            DB::table('classroom_module_user')->insert(['classroom_id' => $class->id, 'module_id' => $data['module_id'], 'user_id' => $teacherId]);
        }

        return $class;
    }

    private function enrollment(Request $request): Enrollment
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'email' => 'required|email|max:255', 'active' => 'required|boolean']);
        $class = $this->context->classrooms($request->user())->findOrFail($data['classroom_id']);
        $student = User::where('role', 'student')->where('active', true)->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])->first();
        if (! $student) {
            throw ValidationException::withMessages(['email' => 'No se ha encontrado un estudiante activo con ese correo.']);
        }
        $enrollment = Enrollment::firstOrNew(['classroom_id' => $class->id, 'student_id' => $student->id]);
        abort_if(! $data['active'] && ! $enrollment->exists, 404);
        $enrollment->fill(['ended_at' => $data['active'] ? null : now()])->save();

        return $enrollment;
    }

    private function person(Request $request, string $role, ?int $id): User
    {
        $actor = $request->user();
        abort_if($id && $actor->role !== 'admin', 403, 'Solo administración puede editar las cuentas existentes.');
        $class = null;
        if ($role === 'student' && ($actor->role !== 'admin' || $request->filled('classroom_id'))) {
            $request->validate(['classroom_id' => 'required|integer'], ['classroom_id.required' => 'Selecciona una clase para el estudiante.']);
            $class = $this->context->classrooms($actor)->findOrFail($request->integer('classroom_id'));
        }
        $data = $request->validate(['name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)], 'password' => ($id ? 'nullable' : 'required').'|string|min:10|max:200', 'active' => 'required|boolean']);
        $data['email'] = Str::lower($data['email']);
        if (User::whereRaw('LOWER(email) = ?', [$data['email']])->when($id, fn (Builder $users) => $users->where('id', '!=', $id))->exists()) {
            throw ValidationException::withMessages(['email' => 'El correo ya está registrado. Usa la matrícula por correo para una cuenta existente.']);
        }
        $model = $id ? User::where('role', $role)->lockForUpdate()->findOrFail($id) : new User(['role' => $role]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($actor->role !== 'admin') {
            $data['active'] = true;
        }
        if (Str::lower($model->email ?? '') !== $data['email']) {
            $model->forceFill(['google_id' => null, 'email_verified_at' => null]);
        }
        $model->fill($data)->save();
        if ($class) {
            Enrollment::updateOrCreate(['classroom_id' => $class->id, 'student_id' => $model->id], ['ended_at' => null]);
        }

        return $model;
    }

    private function rubric(Request $request, bool $copy, ?int $id): Rubric
    {
        $actor = $request->user();
        $model = $id ? Rubric::availableTo($actor)->findOrFail($id) : new Rubric(['owner_id' => $actor->id]);
        if ($copy) {
            abort_unless($id, 404);
            $model = $model->replicate(['demo_key']);
            $model->owner_id = $actor->id;
            $model->name .= ' (copia)';
            $model->save();

            return $model;
        }
        abort_unless(! $id || $model->owner_id === $actor->id || $actor->role === 'admin', 403, 'Solo el propietario puede editar el original. Puedes crear una copia.');
        $data = $request->validate(['name' => 'required|string|max:150', 'kind' => 'required|in:team,transversal', 'cycle_id' => 'nullable|integer|exists:cycles,id', 'level' => 'nullable|integer|between:1,4', 'shared_user_ids' => 'sometimes|array', 'shared_user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)], 'items' => 'required|array|min:1|max:40', 'items.*.key' => 'required|string|max:80|distinct|regex:/^[a-zA-Z0-9_-]+$/', 'items.*.name' => 'required|string|max:150', 'items.*.description' => 'nullable|string|max:2000', 'items.*.module_id' => 'nullable|integer|exists:modules,id', 'items.*.weight' => ['required', 'numeric', 'gt:0', 'max:10000', ChallengeWriter::DECIMAL], 'items.*.levels' => 'required|array|min:2|max:20', 'items.*.levels.*.score' => ['required', 'numeric', 'between:0,10', ChallengeWriter::DECIMAL], 'items.*.levels.*.description' => 'required|string|max:2000']);
        if (empty($data['cycle_id']) !== empty($data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'Selecciona ciclo y nivel juntos, o deja ambos vacíos para una rúbrica general.']);
        }
        foreach ($data['items'] as &$item) {
            if ($data['kind'] === 'transversal') {
                $item['module_id'] = null;
            }
            if (! empty($item['module_id']) && ! Module::whereKey($item['module_id'])->where('cycle_id', $data['cycle_id'] ?? 0)->where('level', $data['level'] ?? 0)->exists()) {
                throw ValidationException::withMessages(['items' => 'Los criterios deben pertenecer a módulos del ciclo y nivel de la rúbrica.']);
            }
        }
        unset($item);
        $model->fill(collect($data)->except('shared_user_ids')->all())->save();
        if (array_key_exists('shared_user_ids', $data)) {
            $model->sharedUsers()->sync($data['shared_user_ids']);
        }

        return $model;
    }
}
