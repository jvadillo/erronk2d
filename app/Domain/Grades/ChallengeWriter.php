<?php

namespace App\Domain\Grades;

use App\Models\Assessment;
use App\Models\AuditEvent;
use App\Models\Challenge;
use App\Models\ModuleGrade;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ChallengeWriter
{
    public const DECIMAL = 'regex:/^[+-]?\d{1,4}(?:\.\d{1,4})?$/';

    public const GRADE_DECIMAL = 'regex:/^[+-]?\d{1,4}(?:\.\d{1,2})?$/';

    public function __construct(private Gradebook $book, private Calculator $calc, private ChallengeRubricEditor $rubrics) {}

    public function change(User $actor, int $id, array $input): array
    {
        Validator::make($input, ['revision' => 'required|integer|min:1', 'action' => 'required|string'])->validate();

        return DB::transaction(function () use ($actor, $id, $input) {
            $challenge = Challenge::lockForUpdate()->findOrFail($id);
            abort_unless($actor->active && $actor->canAccessClassroom($challenge->classroom), 404);
            abort_unless($challenge->classroom->academicYear->is_open, 403, 'El curso académico está cerrado.');
            abort_if($challenge->revision !== (int) $input['revision'], 409, 'Otra persona ha guardado cambios. Actualiza la matriz antes de continuar.');
            $action = $input['action'];
            if (in_array($challenge->status, ['published', 'finished'], true) && $action !== 'reopen' && $action !== 'publish') {
                throw ValidationException::withMessages(['action' => 'El reto está cerrado. Reábrelo con un motivo antes de modificarlo.']);
            }
            $before = $this->book->challenge($challenge);
            switch ($action) {
                case 'rubric': $this->rubrics->apply($actor, $challenge, $input);
                    break;
                case 'participants': $this->participants($actor, $challenge);
                    break;
                case 'teams': $this->teams($actor, $challenge, $input);
                    break;
                case 'allocation': $this->allocation($actor, $challenge, $input);
                    break;
                case 'grades': $this->grades($actor, $challenge, $input);
                    break;
                case 'assess': $this->assess($actor, $challenge, $input);
                    break;
                case 'configure': $this->configure($actor, $challenge, $input);
                    break;
                case 'publish':
                    $this->permit($actor, 'publish_results');
                    if (! $before['complete']) {
                        throw ValidationException::withMessages(['action' => 'Hay evaluaciones pendientes o errores de configuración.']);
                    }
                    if ($challenge->status === 'published') {
                        throw ValidationException::withMessages(['action' => 'El reto ya está publicado.']);
                    }
                    Publication::create(['challenge_id' => $id, 'version' => ($challenge->publications()->max('version') ?? 0) + 1, 'snapshot' => $before, 'published_by' => $actor->id]);
                    $challenge->status = 'published';
                    break;
                case 'reopen':
                    $this->permit($actor, 'publish_results');
                    Validator::make($input, ['reason' => 'required|string|min:5|max:2000'])->validate();
                    abort_unless(in_array($challenge->status, ['finished', 'published'], true), 422, 'El reto no está cerrado.');
                    $challenge->status = 'evaluating';
                    break;
                case 'status':
                    $this->permit($actor, 'manage_challenges');
                    Validator::make($input, ['status' => 'required|in:draft,active,evaluating,finished'])->validate();
                    if ($input['status'] === 'finished' && ! $before['complete']) {
                        throw ValidationException::withMessages(['status' => 'Completa las evaluaciones antes de finalizar.']);
                    }
                    $challenge->status = $input['status'];
                    break;
                default: abort(422, 'Operación desconocida.');
            }
            $challenge->revision++;
            $challenge->save();
            $after = $this->book->challenge($challenge->fresh());
            // Store complete grade evidence, but never user credentials.
            AuditEvent::create(['user_id' => $actor->id, 'challenge_id' => $id, 'action' => $action, 'before' => $before, 'after' => $after, 'reason' => $input['reason'] ?? null]);

            return $after;
        });
    }

    private function permit(User $actor, string $permission): void
    {
        abort_unless($actor->allows($permission), 403, 'No tienes permiso para esta operación.');
    }

    private function correction(User $actor, bool $exists): void
    {
        if ($exists) {
            $this->permit($actor, 'modify_grades');
        }
    }

    private function participants(User $actor, Challenge $challenge): void
    {
        $this->permit($actor, 'manage_teams');
        if ($challenge->students()->exists() || $challenge->teams()->exists() || $challenge->assessments()->exists() || $challenge->moduleGrades()->exists() || $challenge->memberships()->exists() || $challenge->publications()->exists()) {
            throw ValidationException::withMessages(['participants' => 'Solo se pueden incorporar participantes a un reto vacío, sin equipos, evaluaciones ni publicaciones.']);
        }
        $students = $challenge->classroom->students()->where('active', true)->pluck('users.id');
        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['participants' => 'El grupo no tiene estudiantes activos. Asígnalos primero desde Organización → Estudiante.']);
        }
        $challenge->students()->sync($students);
    }

    private function teams(User $actor, Challenge $ch, array $input): void
    {
        $this->permit($actor, 'manage_teams');
        if ($ch->assessments()->exists() || $ch->moduleGrades()->exists() || $ch->memberships()->whereNotNull('allocation')->exists()) {
            throw ValidationException::withMessages(['teams' => 'El equipo ya tiene evaluaciones. Conserva su composición para mantener la trazabilidad.']);
        }
        Validator::make($input, ['teams' => 'required|array|min:1', 'teams.*.name' => 'required|string|max:100|distinct', 'teams.*.students' => 'required|array|min:2|max:5', 'teams.*.students.*' => 'required|integer'], [
            'teams.required' => 'Añade al menos un equipo.',
            'teams.*.name.required' => 'Escribe el nombre del equipo :position.',
            'teams.*.name.distinct' => 'Los nombres de los equipos deben ser distintos.',
            'teams.*.students.required' => 'Selecciona entre 2 y 5 estudiantes para el equipo :position.',
            'teams.*.students.min' => 'Selecciona entre 2 y 5 estudiantes para el equipo :position.',
            'teams.*.students.max' => 'Selecciona entre 2 y 5 estudiantes para el equipo :position.',
        ])->validate();
        $ids = collect($input['teams'])->pluck('students')->flatten();
        if ($ids->unique()->count() !== $ids->count() || $ids->diff($ch->students()->pluck('users.id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['teams' => 'Cada estudiante debe pertenecer al reto y aparecer en un solo equipo.']);
        }
        $ch->teams()->delete();
        foreach ($input['teams'] as $data) {
            $team = $ch->teams()->create(['name' => $data['name']]);
            foreach ($data['students'] as $student) {
                $team->memberships()->create(['challenge_id' => $ch->id, 'student_id' => $student]);
            }
        }
    }

    private function allocation(User $actor, Challenge $ch, array $input): void
    {
        $this->permit($actor, 'evaluate_team');
        abort_unless($ch->distribution_enabled, 422, 'Este reto no utiliza reparto.');
        Validator::make($input, ['team_id' => 'required|integer', 'allocations' => 'required|array|min:2|max:5', 'allocations.*' => ['required', 'numeric', 'between:0,10', self::GRADE_DECIMAL]])->validate();
        $team = $ch->teams()->with('memberships')->findOrFail($input['team_id']);
        $this->correction($actor, $team->memberships->whereNotNull('allocation')->isNotEmpty());
        $keys = collect(array_keys($input['allocations']))->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($keys !== $team->memberships->pluck('student_id')->sort()->values()->all()) {
            throw ValidationException::withMessages(['allocations' => 'Introduce el reparto de todos los integrantes del equipo.']);
        }
        $bookTeam = collect($this->book->challenge($ch)['teams'])->firstWhere('id', $team->id);
        if ($bookTeam['grade'] === null || ! $this->calc->allocationValid($this->calc->number($bookTeam['grade']), $input['allocations'])) {
            $expected = $this->calc->budget($this->calc->number($bookTeam['grade'] ?? '0'))->multipliedBy(count($input['allocations']));
            $actual = $this->calc->number(0);
            foreach ($input['allocations'] as $allocation) {
                $actual = $actual->plus((string) $allocation);
            }
            $difference = $expected->minus($actual);
            $amount = rtrim(rtrim($this->calc->display($difference->abs(), 2), '0'), '.');
            $amount = str_replace('.', ',', $amount);
            $expectedPoints = rtrim(rtrim($this->calc->display($expected, 2), '0'), '.');
            $expectedPoints = str_replace('.', ',', $expectedPoints);
            $direction = $difference->isNegative() ? 'sobra' : 'falta';
            $points = $amount === '1' ? 'punto' : 'puntos';
            throw ValidationException::withMessages(['allocations' => 'El reparto no es válido. Se deben repartir '.($bookTeam['grade'] === null ? 'los puntos de una rúbrica completa' : $expectedPoints.' puntos')." ({$direction} {$amount} {$points})."]);
        }
        foreach ($team->memberships as $member) {
            $member->update(['allocation' => $input['allocations'][$member->student_id]]);
        }
    }

    private function grades(User $actor, Challenge $ch, array $input): void
    {
        Validator::make($input, ['field' => 'required|in:exam,defense,not_enrolled', 'module_id' => 'required|integer', 'entries' => 'required|array|min:1|max:500', 'entries.*.student_id' => 'required|integer|distinct', 'entries.*.value' => ['present', 'nullable'], 'entries.*.date' => 'nullable|date_format:Y-m-d', 'entries.*.notes' => 'nullable|string|max:2000'])->validate();
        $field = $input['field'];
        $this->permit($actor, $field === 'exam' ? 'enter_exams' : 'enter_defenses');
        $module = $ch->modules()->findOrFail($input['module_id']);
        abort_unless($actor->teaches($module->id, $ch->classroom_id), 403, 'Solo los responsables del módulo pueden modificar estas notas.');
        if ($field === 'defense') {
            abort_unless($module->pivot->defense_enabled, 422, 'La defensa de este módulo está desactivada.');
        }
        $students = $ch->students()->pluck('users.id');
        foreach ($input['entries'] as $entry) {
            abort_unless($students->contains((int) $entry['student_id']), 422, 'El estudiante no participa en el reto.');
            Validator::make($entry, ['value' => $field === 'not_enrolled' ? ['required', 'boolean'] : ['nullable', 'numeric', $field === 'exam' ? 'between:0,10' : 'between:-10,10', self::GRADE_DECIMAL]])->validate();
            $grade = ModuleGrade::firstOrNew(['challenge_id' => $ch->id, 'module_id' => $module->id, 'student_id' => $entry['student_id']]);
            if ($field === 'not_enrolled' && $entry['value'] && ($grade->exam !== null || $grade->defense !== null)) {
                throw ValidationException::withMessages(['entries' => 'Retira las notas de este módulo antes de marcar No matriculado.']);
            }
            if ($field !== 'not_enrolled' && $grade->not_enrolled) {
                throw ValidationException::withMessages(['entries' => 'Desmarca No matriculado antes de introducir notas.']);
            }
            $this->correction($actor, $grade->$field !== null);
            $grade->$field = $entry['value'];
            $grade->updated_by = $actor->id;
            if ($field === 'defense') {
                $grade->defense_teacher_id = $entry['value'] === null ? null : $actor->id;
                $grade->defense_date = $entry['value'] === null ? null : ($entry['date'] ?? now()->toDateString());
                $grade->defense_notes = $entry['notes'] ?? null;
            }
            $grade->save();
        }
    }

    private function assess(User $actor, Challenge $ch, array $input): void
    {
        Validator::make($input, ['kind' => 'required|in:team,teacher,self,peer', 'entries' => 'required|array|min:1|max:1000', 'entries.*.subject_id' => 'required|integer', 'entries.*.criterion' => 'required|string|max:80', 'entries.*.level' => 'required|integer|min:0|max:20'])->validate();
        $kind = $input['kind'];
        if ($kind === 'team') {
            $this->permit($actor, 'evaluate_team');
        }
        if ($kind === 'teacher') {
            $this->permit($actor, 'evaluate_transversal');
        }
        if (in_array($kind, ['self', 'peer'], true)) {
            abort_unless($actor->role === 'student' && $ch->students()->where('users.id', $actor->id)->exists(), 403);
            abort_unless(in_array($ch->status, ['active', 'evaluating'], true), 422, 'La evaluación del alumnado no está abierta.');
        }
        $items = collect(($kind === 'team' ? $ch->team_rubric : $ch->transversal_rubric)['items'] ?? [])->keyBy('key');
        $scope = in_array($kind, ['self', 'peer'], true) ? $actor->id : 0;
        foreach ($input['entries'] as $entry) {
            $item = $items->get($entry['criterion']);
            if (! $item || ! isset($item['levels'][$entry['level']])) {
                throw ValidationException::withMessages(['entries' => 'El criterio o nivel no pertenece a la rúbrica del reto.']);
            }
            if ($kind === 'team') {
                abort_unless($ch->teams()->whereKey($entry['subject_id'])->exists(), 422);
                if (! empty($item['module_id'])) {
                    abort_unless($actor->teaches((int) $item['module_id'], $ch->classroom_id), 403);
                }
            } else {
                abort_unless($ch->students()->where('users.id', $entry['subject_id'])->exists(), 422);
            }
            if ($kind === 'self') {
                abort_unless($actor->id === (int) $entry['subject_id'], 403);
            }
            if ($kind === 'peer') {
                $mine = $ch->memberships()->where('student_id', $actor->id)->first();
                $other = $ch->memberships()->where('student_id', $entry['subject_id'])->first();
                abort_unless($mine && $other && $mine->team_id === $other->team_id && $actor->id !== (int) $entry['subject_id'], 403);
            }
            $key = ['challenge_id' => $ch->id, 'kind' => $kind, 'subject_id' => $entry['subject_id'], 'scope_id' => $scope, 'criterion' => $entry['criterion']];
            if (in_array($kind, ['team', 'teacher'], true)) {
                $this->correction($actor, Assessment::where($key)->exists());
            }
            Assessment::updateOrCreate($key, ['level' => $entry['level'], 'updated_by' => $actor->id]);
        }
    }

    private function configure(User $actor, Challenge $ch, array $input): void
    {
        $this->permit($actor, 'manage_challenges');
        Validator::make($input, ['name' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'notes' => 'nullable|string|max:5000', 'weight' => ['required', 'numeric', 'gt:0', 'max:10000', self::DECIMAL], 'distribution_enabled' => 'required|boolean', 'clamp_grade' => 'required|boolean', 'starts_at' => 'nullable|date_format:Y-m-d', 'ends_at' => 'nullable|date_format:Y-m-d|after_or_equal:starts_at', 'component_weights' => 'required|array:transversal,challenge,exam', 'transversal_weights' => 'required|array:self,peer,teacher', 'defenses' => 'required|array'])->validate();
        foreach (['component_weights' => ['transversal', 'challenge', 'exam'], 'transversal_weights' => ['self', 'peer', 'teacher']] as $field => $keys) {
            $rules = [];
            foreach ($keys as $key) {
                $rules[$key] = ['required', 'numeric', 'between:0,100', self::DECIMAL];
            }
            Validator::make($input[$field], $rules)->validate();
            $total = $this->calc->number(0);
            foreach ($input[$field] as $weight) {
                $total = $total->plus((string) $weight);
            }
            if (! $total->isEqualTo(100)) {
                throw ValidationException::withMessages([$field => 'Los porcentajes deben sumar 100.']);
            }
        }
        $hasGrades = $ch->assessments()->exists() || $ch->moduleGrades()->exists() || $ch->memberships()->whereNotNull('allocation')->exists();
        if ($hasGrades) {
            $this->permit($actor, 'modify_grades');
        }
        if ($ch->publications()->exists() && empty($input['reason'])) {
            throw ValidationException::withMessages(['reason' => 'Indica el motivo de la corrección de configuración.']);
        }
        foreach ($input['defenses'] as $id => $enabled) {
            abort_unless($ch->modules()->where('modules.id', $id)->exists() && is_bool($enabled), 422);
            if (! $enabled && $ch->moduleGrades()->where('module_id', $id)->whereNotNull('defense')->exists()) {
                throw ValidationException::withMessages(['defenses' => 'No se puede desactivar una defensa ya registrada.']);
            }
            $ch->modules()->updateExistingPivot($id, ['defense_enabled' => $enabled]);
        }
        $ch->fill(collect($input)->only(['name', 'description', 'notes', 'weight', 'distribution_enabled', 'clamp_grade', 'component_weights', 'transversal_weights', 'starts_at', 'ends_at'])->all());
    }
}
