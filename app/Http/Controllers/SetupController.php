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
use Brick\Math\BigDecimal;
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
    public const SECTIONS = ['courses' => 'Cursos académicos', 'cycles' => 'Ciclos', 'modules' => 'Módulos', 'classrooms' => 'Grupos', 'teachers' => 'Profesor', 'students' => 'Estudiante', 'rubrics' => 'Biblioteca de rúbricas', 'registrations' => 'Solicitudes'];

    public const NAVIGATION_LABELS = ['courses' => 'Cursos académicos', 'cycles' => 'Ciclos', 'modules' => 'Módulos', 'classrooms' => 'Grupos', 'teachers' => 'Profesores', 'students' => 'Estudiantes', 'rubrics' => 'Rúbricas', 'registrations' => 'Solicitudes'];

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
        $classes = $this->context->classrooms($actor)->with('users', 'students', 'catalogModules', 'academicYear', 'periods')->orderBy('name')->get();
        foreach ($classes as $class) {
            foreach ($class->catalogModules as $module) {
                $module->name = $module->pivot->name;
                $module->code = $module->pivot->code;
                $module->setRelation('teachers', $module->teachersFor($class->id)->get(['users.id', 'users.name']));
            }
            $class->setAttribute('can_manage', $actor->canManageClassroom($class));
            $class->setRelation('modules', $class->catalogModules->filter(fn ($module) => $module->pivot->ended_at === null)->values());
            $class->setRelation('retired_modules', $class->catalogModules->filter(fn ($module) => $module->pivot->ended_at !== null)->values());
            $class->unsetRelation('catalogModules');
            $class->periods->loadCount('challenges');
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
            'years' => $section === 'courses' ? AcademicYear::orderByDesc('id')->get() : [],
            'classrooms' => $classes, 'users' => $people,
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->where('active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'cycles' => Cycle::orderBy('name')->get(),
            'modules' => Module::with('cycle')->orderBy('name')->get(),
            'cycleModuleUrl' => route('setup.store', ['entity' => 'cycle-module']),
            'rubricCreateUrl' => route('rubrics.create'),
            'rubrics' => $section === 'rubrics' ? Rubric::availableTo($actor)->with('sharedUsers:id,name')->orderBy('name')->get()->map(fn (Rubric $rubric): array => [...$rubric->toArray(), 'edit_url' => route('rubrics.edit', ['rubric' => $rubric->id])]) : [],
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

    public function rubricEditor(Request $request, ?int $rubric = null): Response
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        $model = $rubric ? Rubric::availableTo($actor)->with('sharedUsers:id,name')->findOrFail($rubric) : null;
        abort_unless($model === null || $actor->role === 'admin' || $model->owner_id === $actor->id, 403, 'Puedes crear una copia de esta rúbrica compartida.');

        return Inertia::render('RubricEditor', [
            'rubric' => $model,
            'cycles' => Cycle::orderBy('name')->get(['id', 'name']),
            'modules' => Module::orderBy('name')->get(['id', 'cycle_id', 'level', 'name', 'code']),
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->where('active', true)->whereKeyNot($actor->id)->orderBy('name')->get(['id', 'name']),
            'saveUrl' => route('setup.store', ['entity' => 'rubric']),
            'libraryUrl' => route('setup.section', ['section' => 'rubrics']),
        ]);
    }

    public function store(Request $request, string $entity): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        abort_unless(in_array($entity, ['year', 'year-status', 'cycle', 'cycle-module', 'module', 'classroom', 'group-module', 'group-periods', 'responsibility', 'student', 'teacher', 'enrollment', 'rubric', 'rubric-copy'], true), 404);
        if (in_array($entity, ['year', 'year-status', 'cycle', 'cycle-module', 'module', 'teacher'], true)) {
            abort_unless($actor->role === 'admin', 403);
        }
        DB::transaction(function () use ($request, $entity, $actor) {
            if (in_array($entity, ['classroom', 'group-module', 'group-periods', 'responsibility', 'enrollment'], true) || ($entity === 'student' && ($actor->role !== 'admin' || $request->filled('classroom_id')))) {
                $this->context->requireWritable($request);
            }
            $request->validate(['id' => 'nullable|integer']);
            $id = $request->integer('id') ?: null;
            $model = match ($entity) {
                'year' => $this->year($request, $id),
                'year-status' => $this->yearStatus($request),
                'cycle' => $this->cycle($request, $id),
                'cycle-module' => $this->cycleModule($request),
                'module' => $this->module($request, $id),
                'classroom' => $this->classroom($request, $id),
                'group-module' => $this->groupModule($request),
                'group-periods' => $this->groupPeriods($request),
                'responsibility' => $this->responsibility($request),
                'enrollment' => $this->enrollment($request),
                'student', 'teacher' => $this->person($request, $entity, $id),
                'rubric', 'rubric-copy' => $this->rubric($request, $entity === 'rubric-copy', $id),
            };
            AuditEvent::create(['user_id' => $actor->id, 'action' => 'setup.'.$entity, 'after' => $model->only(['id', 'name', 'role', 'is_open', 'owner_id', 'classroom_id', 'student_id', 'ended_at'])]);
        });

        return ($entity === 'rubric' ? redirect()->route('setup.section', ['section' => 'rubrics']) : back())->with('success', 'Información guardada.');
    }

    private function year(Request $request, ?int $id): AcademicYear
    {
        $model = $id ? AcademicYear::lockForUpdate()->findOrFail($id) : new AcademicYear;
        abort_if($id && ! $model->is_open, 403, 'Reabre el curso académico antes de editarlo.');
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('academic_years')->ignore($id)]]);
        $model->fill($data)->save();

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

    private function cycleModule(Request $request): Module
    {
        $data = $request->validate(['cycle_id' => 'required|integer|exists:cycles,id', 'module_id' => 'required|integer|exists:modules,id', 'active' => 'required|boolean', 'level' => 'required|integer|between:1,4']);
        Cycle::lockForUpdate()->findOrFail($data['cycle_id']);
        $module = Module::lockForUpdate()->findOrFail($data['module_id']);
        if (! $data['active']) {
            abort_unless($module->cycle_id === (int) $data['cycle_id'], 404);
            $module->update(['cycle_id' => null]);

            return $module;
        }
        if ($module->cycle_id !== null) {
            throw ValidationException::withMessages(['module_id' => 'Solo puedes añadir módulos que no estén asociados a un ciclo.']);
        }
        if (Module::where('cycle_id', $data['cycle_id'])->where('level', $data['level'])->where('code', $module->code)->exists()) {
            throw ValidationException::withMessages(['module_id' => 'Ya existe un módulo con ese código en el curso seleccionado.']);
        }
        $module->update(['cycle_id' => $data['cycle_id'], 'level' => $data['level']]);

        return $module;
    }

    private function module(Request $request, ?int $id): Module
    {
        $model = $id ? Module::findOrFail($id) : new Module;
        $data = $request->validate(['name' => 'required|string|max:150', 'code' => ['required', 'string', 'max:30', Rule::unique('modules')->where('cycle_id', $request->input('cycle_id'))->where('level', $request->input('level'))->ignore($id)], 'cycle_id' => 'nullable|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4']);
        if ($id && ($model->cycle_id !== (empty($data['cycle_id']) ? null : (int) $data['cycle_id']) || $model->level !== (int) $data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'Un módulo existente conserva su ciclo y nivel.']);
        }
        $model->fill($data)->save();

        return $model;
    }

    private function classroom(Request $request, ?int $id): Classroom
    {
        $actor = $request->user();
        $year = $this->context->year();
        $model = $id ? $this->context->classrooms($actor)->lockForUpdate()->findOrFail($id) : new Classroom;
        abort_if($id && ! $actor->canManageClassroom($model), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('classrooms')->where('academic_year_id', $year->id)->ignore($id)], 'cycle_id' => 'required|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4', 'period_count' => 'sometimes|integer|between:1,12', 'user_ids' => 'present|array', 'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)], 'owner_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)]]);
        $ownerId = $id ? $model->owner_id : $actor->id;
        if (isset($data['owner_id']) && (int) $data['owner_id'] !== $ownerId) {
            abort_unless($actor->role === 'admin', 403, 'Solo administración puede transferir la propiedad.');
            $ownerId = (int) $data['owner_id'];
        }
        if ($id && ($model->cycle_id !== (int) $data['cycle_id'] || $model->level !== (int) $data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'El grupo conserva su ciclo y nivel. Crea otro grupo para cambiarlos.']);
        }
        $model->fill(['name' => $data['name'], 'academic_year_id' => $year->id, 'cycle_id' => $data['cycle_id'], 'level' => $data['level'], 'owner_id' => $ownerId, 'cycle_name' => $id ? $model->cycle_name : Cycle::findOrFail($data['cycle_id'])->name])->save();
        $model->users()->sync(array_unique([...$data['user_ids'], $ownerId]));
        if (! $id) {
            $model->syncCatalog();
            for ($position = 1; $position <= ($data['period_count'] ?? 3); $position++) {
                $model->periods()->create(['name' => $position.'.ª Evaluación', 'position' => $position]);
            }
        }

        return $model;
    }

    private function groupModule(Request $request): Classroom
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'module_id' => 'required|integer', 'active' => 'required|boolean']);
        $class = $this->context->classrooms($request->user())->lockForUpdate()->findOrFail($data['classroom_id']);
        $module = Module::where('cycle_id', $class->cycle_id)->where('level', $class->level)->find($data['module_id']);
        if (! $module) {
            throw ValidationException::withMessages(['module_id' => 'Selecciona un módulo del mismo ciclo y nivel que el grupo.']);
        }
        $attached = $class->catalogModules()->find($module->id);
        if (! $data['active'] && ! $attached) {
            abort(404);
        }
        if ($attached) {
            $class->catalogModules()->updateExistingPivot($module->id, ['ended_at' => $data['active'] ? null : now()]);
        } else {
            $class->catalogModules()->attach($module->id, ['name' => $module->name, 'code' => $module->code]);
        }

        return $class;
    }

    private function groupPeriods(Request $request): Classroom
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'periods' => 'required|array|list|min:1|max:12', 'periods.*.id' => 'nullable|integer|min:1|distinct', 'periods.*.name' => 'required|string|max:100|distinct']);
        $class = $this->context->classrooms($request->user())->lockForUpdate()->findOrFail($data['classroom_id']);
        $existing = $class->periods()->withCount('challenges')->get();
        $ids = collect($data['periods'])->pluck('id')->filter();
        if ($ids->diff($existing->pluck('id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['periods' => 'Las evaluaciones deben pertenecer a este grupo.']);
        }
        foreach ($existing as $period) {
            $replacement = collect($data['periods'])->first(fn ($item) => (int) ($item['id'] ?? 0) === $period->id);
            if ($period->challenges_count && (! $replacement || $replacement['name'] !== $period->name)) {
                throw ValidationException::withMessages(['periods' => 'Las evaluaciones con retos conservan su nombre y no se pueden eliminar. Puedes añadir evaluaciones o quitar las que no tengan retos.']);
            }
        }
        $class->periods()->whereNotIn('id', $ids)->delete();
        $class->periods()->increment('position', 100);
        $existing = $class->periods()->get();
        foreach ($data['periods'] as $index => $item) {
            $period = empty($item['id']) ? $class->periods()->make() : $existing->firstWhere('id', (int) $item['id']);
            $period->fill(['name' => $item['name'], 'position' => $index + 1])->save();
        }

        return $class;
    }

    private function responsibility(Request $request): Classroom
    {
        $data = $request->validate(['classroom_id' => 'required|integer', 'module_id' => 'required|integer', 'teacher_ids' => 'present|array', 'teacher_ids.*' => 'integer|distinct']);
        $class = $this->context->classrooms($request->user())->findOrFail($data['classroom_id']);
        abort_unless($request->user()->canManageClassroom($class), 403);
        $class->modules()->findOrFail($data['module_id']);
        $members = $class->users()->where('users.active', true)->pluck('users.id');
        if (collect($data['teacher_ids'])->diff($members)->isNotEmpty()) {
            throw ValidationException::withMessages(['teacher_ids' => 'Los responsables deben ser profesores activos de este grupo.']);
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
        $student = User::where('role', 'student')->when($data['active'], fn (Builder $users) => $users->where('active', true))->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])->first();
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
            $request->validate(['classroom_id' => 'required|integer'], ['classroom_id.required' => 'Selecciona un grupo para el estudiante.']);
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
        $data = $request->validate(['name' => 'required|string|max:150', 'kind' => 'required|in:team,transversal', 'cycle_id' => 'nullable|integer|exists:cycles,id', 'level' => 'nullable|integer|between:1,4', 'shared_user_ids' => 'sometimes|array', 'shared_user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)], 'items' => 'required|array|list|min:1|max:40', 'items.*.key' => 'required|string|max:80|distinct|regex:/^[a-zA-Z0-9_-]+$/', 'items.*.name' => 'required|string|max:150', 'items.*.description' => 'nullable|string|max:2000', 'items.*.module_id' => 'nullable|integer|exists:modules,id', 'items.*.weight' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2', ChallengeWriter::DECIMAL], 'items.*.levels' => 'required|array|list|min:2|max:20', 'items.*.levels.*.score' => ['required', 'numeric', 'between:0,10', ChallengeWriter::DECIMAL], 'items.*.levels.*.description' => 'required|string|max:2000']);
        if (empty($data['cycle_id']) !== empty($data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'Selecciona ciclo y nivel juntos, o deja ambos vacíos para una rúbrica general.']);
        }
        $scores = array_map(fn (array $level): float => (float) $level['score'], $data['items'][0]['levels']);
        $totalWeight = BigDecimal::zero();
        foreach ($data['items'] as $index => $item) {
            if (array_map(fn (array $level): float => (float) $level['score'], $item['levels']) !== $scores) {
                throw ValidationException::withMessages(["items.$index.levels" => 'Todos los criterios deben tener los mismos niveles y la misma nota en cada columna.']);
            }
            $totalWeight = $totalWeight->plus($item['weight']);
        }
        if (! $totalWeight->isEqualTo(100)) {
            throw ValidationException::withMessages(['items' => 'Los pesos de los criterios deben sumar exactamente 100 %.']);
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
