<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Domain\Catalog;
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
use Illuminate\Database\Eloquent\Collection;
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

    public const ADMIN_SECTIONS = ['teachers', 'registrations'];

    public function __construct(private AcademicContext $context, private Catalog $catalog) {}

    public function index(Request $request, ?string $section = null): Response|RedirectResponse
    {
        $actor = $request->user();
        abort_if($actor->role === 'student', 403);
        if ($section === null) {
            return redirect()->route('setup.section', ['section' => $actor->role === 'admin' ? 'courses' : 'classrooms']);
        }
        abort_unless(isset(self::SECTIONS[$section]), 404);
        abort_if(in_array($section, self::ADMIN_SECTIONS, true) && $actor->role !== 'admin', 403);
        $classes = $this->context->classrooms($actor)->with('users:users.id,users.name', 'periods:id,classroom_id,name')->withCount('students')->orderBy('name')->get();
        $classes->each(fn (Classroom $class) => $class->setAttribute('url', route('groups.show', ['classroom' => $class->id])));
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
            'years' => $section === 'courses' ? ($actor->role === 'admin' ? AcademicYear::orderByDesc('id') : $this->context->availableYears($actor))->get() : [],
            'openYears' => $section === 'classrooms' ? $this->openYears() : [],
            'currentYearId' => $this->context->year()?->id,
            'classrooms' => $classes, 'users' => $people,
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->where('active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'cycles' => Cycle::orderBy('name')->get(),
            'modules' => Module::with('cycle')->orderBy('name')->get(),
            'cycleModuleUrl' => route('setup.store', ['entity' => 'cycle-module']),
            'rubricCreateUrl' => route('rubrics.create'),
            'rubrics' => $section === 'rubrics' ? Rubric::availableTo($actor)->with(['teamChallenge:id,name,team_rubric_id', 'transversalChallenge:id,name,transversal_rubric_id'])->orderBy('name')->get()->map(fn (Rubric $rubric): array => [
                ...$rubric->makeHidden(['teamChallenge', 'transversalChallenge'])->toArray(), 'edit_url' => route('rubrics.edit', ['rubric' => $rubric->id]),
                'challenge' => ($challenge = $rubric->linkedChallenge()) ? ['name' => $challenge->name, 'editor_url' => route('challenges.rubrics.edit', ['challenge' => $challenge, 'kind' => $rubric->kind])] : null,
            ]) : [],
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

    /**
     * Suggests active students not yet enrolled in the group whose name or email contains the text.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        abort_unless($request->user()->allows('manage_students'), 403);
        $data = $request->validate(['q' => 'required|string|min:3|max:100', 'classroom_id' => 'required|integer']);
        $class = $this->context->classrooms($request->user())->findOrFail($data['classroom_id']);
        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], Str::lower(trim($data['q']))).'%';
        $students = User::where('role', 'student')->where('active', true)
            ->where(fn (Builder $match) => $match->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$pattern])->orWhereRaw("LOWER(email) LIKE ? ESCAPE '\\'", [$pattern]))
            ->whereDoesntHave('enrollments', fn (Builder $enrollments) => $enrollments->where('classroom_id', $class->id)->whereNull('ended_at'))
            ->orderBy('name')->limit(10)->get(['id', 'name', 'email']);

        return response()->json(['students' => $students]);
    }

    public function group(Request $request, int $classroom): Response
    {
        $actor = $request->user();
        abort_if($actor->role === 'student', 403);
        $class = $this->context->classrooms($actor)->with('users:users.id,users.name,users.active', 'students:users.id,users.name,users.email,users.active', 'catalogModules', 'periods')->findOrFail($classroom);
        foreach ($class->catalogModules as $module) {
            $module->name = $module->pivot->name;
            $module->code = $module->pivot->code;
        }
        $class->setAttribute('can_manage', $actor->canManageClassroom($class));
        $class->setRelation('modules', $class->catalogModules->filter(fn ($module) => $module->pivot->ended_at === null)->values());
        $class->setRelation('retired_modules', $class->catalogModules->filter(fn ($module) => $module->pivot->ended_at !== null)->values());
        $class->unsetRelation('catalogModules');
        $class->periods->loadCount('challenges');

        return Inertia::render('Group', [
            'group' => $class,
            'catalog' => Module::where('cycle_id', $class->cycle_id)->where('level', $class->level)->orderBy('name')->get(['id', 'name', 'code']),
            'teachers' => User::whereIn('role', ['teacher', 'admin'])->where('active', true)->orderBy('name')->get(['id', 'name']),
            'openYears' => $this->openYears(),
            'groupsUrl' => route('setup.section', ['section' => 'classrooms']),
            'duplicateUrl' => route('groups.duplicate', ['classroom' => $class->id]),
        ]);
    }

    /**
     * Copies the structure of a group (cycle, level, active modules, evaluations and chosen teachers)
     * into a new group of an open academic year. Activity is never copied; enrollments only on request.
     */
    public function duplicate(Request $request, int $classroom): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        [$copy, $summary] = DB::transaction(function () use ($request, $actor, $classroom) {
            $source = $this->context->classrooms($actor)->with('periods', 'modules', 'cycle')->findOrFail($classroom);
            $year = $this->targetYear($request);
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100', Rule::unique('classrooms')->where('academic_year_id', $year->id)],
                'user_ids' => 'present|array', 'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)],
                'student_ids' => 'present|array', 'student_ids.*' => 'integer|distinct',
            ], ['name.unique' => 'Ya existe un grupo con ese nombre en el curso académico de destino.', 'user_ids.*.exists' => 'Solo se pueden incorporar profesores con la cuenta activa.']);
            $students = $source->students()->where('users.active', true)->whereIn('users.id', $data['student_ids'])->pluck('users.id');
            if ($students->count() !== count($data['student_ids'])) {
                throw ValidationException::withMessages(['student_ids' => 'Solo puedes matricular alumnado activo del grupo de origen.']);
            }
            $copy = Classroom::create(['academic_year_id' => $year->id, 'name' => $data['name'], 'cycle_id' => $source->cycle_id, 'cycle_name' => $source->cycle?->name ?? $source->cycle_name, 'level' => $source->level, 'owner_id' => $actor->id]);
            $copy->users()->sync(array_unique([...$data['user_ids'], $actor->id]));
            $modules = Module::whereIn('id', $source->modules->modelKeys())->where('cycle_id', $source->cycle_id)->where('level', $source->level)->get();
            foreach ($modules as $module) {
                $copy->catalogModules()->attach($module->id, ['name' => $module->name, 'code' => $module->code]);
            }
            foreach ($source->periods as $period) {
                $copy->periods()->create(['name' => $period->name, 'position' => $period->position]);
            }
            foreach ($students as $studentId) {
                Enrollment::create(['classroom_id' => $copy->id, 'student_id' => $studentId]);
            }
            $summary = ['source_id' => $source->id, 'modules' => $modules->count(), 'teachers' => $copy->users()->count(), 'students' => $students->count()];
            AuditEvent::create(['user_id' => $actor->id, 'action' => 'setup.classroom-duplicate', 'after' => [...$copy->only(['id', 'name', 'academic_year_id', 'owner_id']), ...$summary]]);

            return [$copy, $summary];
        });
        $this->context->remember($request, $copy->academicYear);

        return redirect()->route('groups.show', ['classroom' => $copy->id])->with('success', "Grupo duplicado con {$summary['modules']} módulos, {$summary['teachers']} profesores y {$summary['students']} estudiantes. No se han copiado retos ni notas.");
    }

    /**
     * Open academic years where a group can be created.
     */
    private function openYears(): Collection
    {
        return AcademicYear::where('is_open', true)->orderByDesc('created_at')->orderByDesc('id')->get(['id', 'name', 'is_open']);
    }

    /**
     * Resolves the open academic year chosen for a new group, or the active one when none is given.
     */
    private function targetYear(Request $request): AcademicYear
    {
        if (! $request->has('academic_year_id')) {
            return $this->context->requireWritable($request);
        }
        $data = $request->validate(['academic_year_id' => 'required|integer'], ['academic_year_id.required' => 'Selecciona o crea un curso académico.']);
        $year = $this->context->availableYears($request->user())->lockForUpdate()->find($data['academic_year_id']);
        if (! $year?->is_open) {
            throw ValidationException::withMessages(['academic_year_id' => 'Selecciona un curso académico abierto. Los cursos cerrados son de solo lectura.']);
        }

        return $year;
    }

    public function rubricEditor(Request $request, ?int $rubric = null): Response|RedirectResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        $model = $rubric ? Rubric::availableTo($actor)->findOrFail($rubric) : null;
        if ($challenge = $model?->linkedChallenge()) {
            return redirect()->route('challenges.rubrics.edit', ['challenge' => $challenge, 'kind' => $model->kind]);
        }

        return Inertia::render('RubricEditor', [
            'rubric' => $model,
            'cycles' => Cycle::orderBy('name')->get(['id', 'name']),
            'modules' => Module::orderBy('name')->get(['id', 'cycle_id', 'level', 'name', 'code']),
            'saveUrl' => route('setup.store', ['entity' => 'rubric']),
            'libraryUrl' => route('setup.section', ['section' => 'rubrics']),
        ]);
    }

    public function store(Request $request, string $entity): RedirectResponse|JsonResponse
    {
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['admin', 'teacher'], true), 403);
        abort_unless(in_array($entity, ['year', 'year-status', 'cycle', 'cycle-module', 'module', 'classroom', 'group-module', 'group-periods', 'student', 'teacher', 'enrollment', 'rubric', 'rubric-copy'], true), 404);
        if (in_array($entity, ['year-status', 'cycle-module', 'teacher'], true) || (in_array($entity, ['year', 'cycle', 'module'], true) && $request->filled('id'))) {
            abort_unless($actor->role === 'admin', 403, 'Solo administración puede modificar, cerrar o reorganizar el catálogo compartido y las cuentas del profesorado.');
        }
        $model = DB::transaction(function () use ($request, $entity, $actor) {
            if (in_array($entity, ['group-module', 'group-periods', 'enrollment'], true) || ($entity === 'student' && ($actor->role !== 'admin' || $request->filled('classroom_id')))) {
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
                'enrollment' => $this->enrollment($request),
                'student', 'teacher' => $this->person($request, $entity, $id),
                'rubric', 'rubric-copy' => $this->rubric($request, $entity === 'rubric-copy', $id),
            };
            AuditEvent::create(['user_id' => $actor->id, 'action' => 'setup.'.$entity, 'after' => $model->only(['id', 'name', 'role', 'is_open', 'owner_id', 'classroom_id', 'student_id', 'ended_at'])]);

            return $model;
        });
        if ($request->wantsJson() && in_array($entity, ['year', 'cycle', 'module'], true)) {
            return response()->json(['item' => $model->toArray()]);
        }
        if ($entity === 'classroom' && ! $request->integer('id')) {
            $this->context->remember($request, $model->academicYear);

            return redirect()->route('groups.show', ['classroom' => $model->id])->with('success', 'Grupo creado. Revisa sus módulos, profesores y estudiantes.');
        }

        return ($entity === 'rubric' ? redirect()->route('setup.section', ['section' => 'rubrics']) : back())->with('success', 'Información guardada.');
    }

    private function year(Request $request, ?int $id): AcademicYear
    {
        $model = $id ? AcademicYear::lockForUpdate()->findOrFail($id) : new AcademicYear(['is_open' => true]);
        abort_if($id && ! $model->is_open, 403, 'Reabre el curso académico antes de editarlo.');
        $data = $request->validate(['name' => 'required|string|max:100']);

        return $this->catalog->saveYear($model, $data['name']);
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
        $data = $request->validate(['name' => 'required|string|max:150', 'code' => 'required|string|max:30']);
        $model = $id ? Cycle::lockForUpdate()->findOrFail($id) : new Cycle;

        return $this->catalog->saveCycle($model, $data['name'], $data['code']);
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
        $module->fill(['cycle_id' => (int) $data['cycle_id'], 'level' => (int) $data['level']]);
        try {
            $this->catalog->assertModuleIsNew($module);
        } catch (ValidationException $duplicate) {
            throw ValidationException::withMessages(['module_id' => collect($duplicate->errors())->flatten()->first()]);
        }

        return $this->catalog->persist($module, 'module_id', 'El ciclo acaba de recibir un módulo con ese nombre o código.');
    }

    private function module(Request $request, ?int $id): Module
    {
        $model = $id ? Module::lockForUpdate()->findOrFail($id) : new Module;
        $data = $request->validate(['name' => 'required|string|max:150', 'code' => 'required|string|max:30', 'cycle_id' => [$request->user()->role === 'admin' ? 'nullable' : 'required', 'integer', 'exists:cycles,id'], 'level' => 'required|integer|between:1,4'], ['cycle_id.required' => 'Selecciona el ciclo del módulo.']);
        $cycleId = empty($data['cycle_id']) ? null : (int) $data['cycle_id'];
        if ($id && ($model->cycle_id !== $cycleId || $model->level !== (int) $data['level'])) {
            throw ValidationException::withMessages(['cycle_id' => 'Un módulo existente conserva su ciclo y curso / nivel.']);
        }
        if ($cycleId) {
            Cycle::lockForUpdate()->findOrFail($cycleId);
        }

        return $this->catalog->saveModule($model, ['name' => $data['name'], 'code' => $data['code'], 'cycle_id' => $cycleId, 'level' => (int) $data['level']]);
    }

    private function classroom(Request $request, ?int $id): Classroom
    {
        $actor = $request->user();
        $year = $id ? $this->context->requireWritable($request) : $this->targetYear($request);
        $model = $id ? $this->context->classrooms($actor)->lockForUpdate()->findOrFail($id) : new Classroom;
        abort_if($id && ! $actor->canManageClassroom($model), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('classrooms')->where('academic_year_id', $year->id)->ignore($id)], 'cycle_id' => 'required|integer|exists:cycles,id', 'level' => 'required|integer|between:1,4', 'period_count' => 'sometimes|integer|between:1,12', 'module_ids' => 'sometimes|array', 'module_ids.*' => 'integer|distinct', 'user_ids' => 'present|array', 'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)], 'owner_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['teacher', 'admin'])->where('active', true)]], ['name.unique' => 'Ya existe un grupo con ese nombre en este curso académico.']);
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
        if (! $id && isset($data['module_ids'])) {
            $modules = Module::where('cycle_id', $model->cycle_id)->where('level', $model->level)->whereIn('id', $data['module_ids'])->get();
            if ($modules->count() !== count($data['module_ids'])) {
                throw ValidationException::withMessages(['module_ids' => 'Selecciona módulos del ciclo y curso / nivel del grupo.']);
            }
            foreach ($modules as $module) {
                $model->catalogModules()->attach($module->id, ['name' => $module->name, 'code' => $module->code]);
            }
        } elseif (! $id) {
            $model->syncCatalog();
        }
        if (! $id) {
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
        abort_unless($request->user()->canManageClassroom($class), 403, 'Solo el propietario del grupo o administración pueden cambiar sus módulos.');
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
        abort_unless($request->user()->canManageClassroom($class), 403, 'Solo el propietario del grupo o administración pueden cambiar sus evaluaciones.');
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
        if ($id && ($challenge = $model->linkedChallenge())) {
            throw ValidationException::withMessages(['name' => "Esta rúbrica pertenece al reto «{$challenge->name}». Edítala desde el reto."]);
        }
        $data = $request->validate(['name' => 'required|string|max:150', 'kind' => 'required|in:team,transversal', 'cycle_id' => 'nullable|integer|exists:cycles,id', 'level' => 'nullable|integer|between:1,4', 'items' => 'required|array|list|min:1|max:40', 'items.*.key' => 'required|string|max:80|distinct|regex:/^[a-zA-Z0-9_-]+$/', 'items.*.name' => 'required|string|max:150', 'items.*.description' => 'nullable|string|max:2000', 'items.*.module_id' => 'nullable|integer|exists:modules,id', 'items.*.weight' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2', ChallengeWriter::DECIMAL], 'items.*.levels' => 'required|array|list|min:2|max:20', 'items.*.levels.*.score' => ['required', 'numeric', 'between:0,10', ChallengeWriter::GRADE_DECIMAL], 'items.*.levels.*.description' => 'required|string|max:2000']);
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
        $model->fill($data)->save();

        return $model;
    }
}
