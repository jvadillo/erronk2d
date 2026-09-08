<?php

namespace App\Domain\Grades;

use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\User;
use Brick\Math\BigRational;
use Illuminate\Support\Collection;

final class Gradebook
{
    public function __construct(private Calculator $calc) {}

    private function rubric(array $rubric, Collection $assessments): ?BigRational
    {
        $values = [];
        $weights = [];
        foreach ($rubric['items'] ?? [] as $item) {
            $key = $item['key'];
            $weights[$key] = $item['weight'];
            $assessment = $assessments->firstWhere('criterion', $key);
            $values[$key] = $assessment ? ($item['levels'][$assessment->level]['score'] ?? null) : null;
        }

        return $this->calc->weighted($values, $weights);
    }

    public function challenge(Challenge $challenge): array
    {
        $challenge->load(['students', 'modules.teachers', 'teams.memberships', 'assessments', 'moduleGrades', 'classroom.academicYear', 'period']);
        $c = $this->calc;
        $issues = [];
        $teams = [];
        $rows = [];
        $assessments = $challenge->assessments;
        if ($challenge->modules->isEmpty()) {
            $issues[] = 'El reto no tiene módulos.';
        }
        if ($challenge->students->isEmpty()) {
            $issues[] = 'El reto no tiene estudiantes.';
        }
        foreach ($challenge->modules as $module) {
            if ($module->teachers->isEmpty()) {
                $issues[] = "{$module->code}: sin profesor responsable.";
            }
        }
        foreach (['team_rubric' => 'reto', 'transversal_rubric' => 'transversales'] as $field => $label) {
            if (empty($challenge->$field['items'])) {
                $issues[] = "Rúbrica de {$label} sin ítems.";
            }
        }
        foreach ($challenge->teams as $team) {
            $teamGrade = $this->rubric($challenge->team_rubric, $assessments->where('kind', 'team')->where('subject_id', $team->id));
            $members = $team->memberships;
            $validSize = $members->count() >= 2 && $members->count() <= 5;
            if (! $validSize) {
                $issues[] = "{$team->name}: debe tener entre 2 y 5 estudiantes.";
            }
            $valid = $teamGrade !== null && $validSize && (! $challenge->distribution_enabled || $c->allocationValid($teamGrade, $members->pluck('allocation')->all()));
            $teams[$team->id] = [
                'id' => $team->id, 'name' => $team->name, 'members' => $members->toArray(),
                'grade' => $teamGrade === null ? null : $c->display($c->budget($teamGrade), 4),
                'rubric_exact' => $teamGrade === null ? null : (string) $teamGrade,
                'points' => $teamGrade === null ? null : $c->display($c->budget($teamGrade)->multipliedBy($members->count()), 4),
                'distribution_valid' => $valid,
            ];
        }
        foreach ($challenge->students as $student) {
            $membership = $challenge->teams->flatMap->memberships->firstWhere('student_id', $student->id);
            $team = $membership ? $teams[$membership->team_id] : null;
            $pending = [];
            if (! $membership) {
                $pending[] = 'Sin equipo';
            }
            if ($team && $team['grade'] === null) {
                $pending[] = 'Rúbrica del equipo';
            }
            if ($team && $challenge->distribution_enabled && ! $team['distribution_valid']) {
                $pending[] = 'Reparto pendiente o inválido';
            }
            $own = $assessments->where('subject_id', $student->id);
            $self = $this->rubric($challenge->transversal_rubric, $own->where('kind', 'self')->where('scope_id', $student->id));
            $teacher = $this->rubric($challenge->transversal_rubric, $own->where('kind', 'teacher')->where('scope_id', 0));
            $peerValues = [];
            foreach ($team['members'] ?? [] as $mate) {
                if ($mate['student_id'] !== $student->id) {
                    $peerValues[$mate['student_id']] = $this->rubric($challenge->transversal_rubric, $own->where('kind', 'peer')->where('scope_id', $mate['student_id']));
                }
            }
            $peer = $c->mean($peerValues);
            $transversal = $c->weighted(['self' => $self, 'peer' => $peer, 'teacher' => $teacher], $challenge->transversal_weights);
            foreach (['self' => ['Autoevaluación', $self], 'peer' => ['Coevaluación', $peer], 'teacher' => ['Evaluación docente', $teacher]] as $key => [$label, $value]) {
                if ($value === null && $c->number((string) $challenge->transversal_weights[$key])->isGreaterThan(0)) {
                    $pending[] = $label;
                }
            }
            $base = null;
            if ($team && $team['distribution_valid']) {
                $base = $c->number($challenge->distribution_enabled ? $membership->allocation : $team['rubric_exact']);
            }
            $defenses = [];
            $modules = [];
            foreach ($challenge->modules as $module) {
                $grade = $challenge->moduleGrades->where('student_id', $student->id)->firstWhere('module_id', $module->id);
                if ($module->pivot->defense_enabled) {
                    $defenses[$module->id] = $grade?->defense;
                    if ($grade?->defense === null) {
                        $pending[] = "Defensa {$module->code}";
                    }
                }
                $modules[$module->id] = [
                    'exam' => $grade?->exam, 'defense' => $grade?->defense,
                    'defense_date' => $grade?->defense_date, 'defense_notes' => $grade?->defense_notes,
                    'defense_teacher_id' => $grade?->defense_teacher_id,
                ];
            }
            $result = $c->challenge($base, $defenses, $challenge->clamp_grade);
            foreach ($challenge->modules as $module) {
                $exam = $modules[$module->id]['exam'];
                if ($exam === null && $c->number((string) $challenge->component_weights['exam'])->isGreaterThan(0)) {
                    $pending[] = "Examen {$module->code}";
                }
                $final = $c->weighted(['transversal' => $transversal, 'challenge' => $result['final'], 'exam' => $exam], $challenge->component_weights);
                $modules[$module->id]['final'] = $c->display($final);
                $modules[$module->id]['exact'] = $final === null ? null : (string) $final;
            }
            $rows[] = [
                'id' => $student->id, 'name' => $student->name, 'team_id' => $membership?->team_id,
                'team_name' => $team['name'] ?? null, 'team_grade' => $team['grade'] ?? null,
                'allocation' => $membership?->allocation, 'base' => $c->display($base, 4),
                'self' => $c->display($self), 'peer' => $c->display($peer), 'teacher' => $c->display($teacher),
                'transversal' => $c->display($transversal), 'defenses_total' => $c->display($result['defenses'], 4),
                'challenge_raw' => $c->display($result['raw'], 4), 'challenge_final' => $c->display($result['final'], 4),
                'modules' => $modules, 'pending' => $pending,
            ];
        }

        return [
            'challenge' => $challenge->only(['id', 'name', 'description', 'notes', 'status', 'weight', 'revision', 'classroom_id', 'period_id', 'starts_at', 'ends_at', 'distribution_enabled', 'clamp_grade', 'component_weights', 'transversal_weights', 'team_rubric', 'transversal_rubric']),
            'classroom' => $challenge->classroom->name, 'year' => $challenge->classroom->academicYear->name, 'period' => $challenge->period->name,
            'modules' => $challenge->modules->map(fn ($m) => ['id' => $m->id, 'code' => $m->code, 'name' => $m->name, 'defense_enabled' => (bool) $m->pivot->defense_enabled, 'teachers' => $m->teachers->map->only(['id', 'name'])->all()])->all(),
            'teams' => array_values($teams), 'rows' => $rows, 'issues' => $issues,
            'assessments' => $assessments->map->only(['kind', 'subject_id', 'scope_id', 'criterion', 'level', 'updated_by', 'updated_at'])->all(),
            'complete' => count($rows) > 0 && count($issues) === 0 && collect($rows)->every(fn ($r) => count($r['pending']) === 0),
        ];
    }

    public function report(Classroom $classroom): array
    {
        $classroom->load(['academicYear.periods', 'modules']);
        $challenges = $classroom->challenges()->with('publications')->get();
        $books = $challenges->mapWithKeys(fn ($ch) => [$ch->id => $this->challenge($ch)]);
        $rows = [];
        foreach (User::where('role', 'student')->where(function ($query) use ($classroom) {
            $query->where('classroom_id', $classroom->id)->orWhereIn('id', function ($participants) use ($classroom) {
                $participants->select('challenge_student.user_id')->from('challenge_student')->join('challenges', 'challenges.id', '=', 'challenge_student.challenge_id')->where('challenges.classroom_id', $classroom->id);
            });
        })->orderBy('name')->orderBy('id')->get() as $student) {
            foreach ($classroom->modules as $module) {
                $periodGrades = [];
                $periods = [];
                foreach ($classroom->academicYear->periods as $period) {
                    $values = [];
                    $weights = [];
                    $details = [];
                    foreach ($challenges->where('period_id', $period->id) as $ch) {
                        $book = $books[$ch->id];
                        if (! collect($book['modules'])->contains('id', $module->id)) {
                            continue;
                        }
                        $row = collect($book['rows'])->firstWhere('id', $student->id);
                        $values[$ch->id] = isset($row['modules'][$module->id]['exact']) ? $this->calc->number($row['modules'][$module->id]['exact']) : null;
                        $weights[$ch->id] = $ch->weight;
                        $details[] = ['id' => $ch->id, 'name' => $ch->name, 'weight' => $ch->weight, 'grade' => $row['modules'][$module->id]['final'] ?? null, 'status' => $ch->status];
                    }
                    $periodGrades[$period->id] = $this->calc->weighted($values, $weights);
                    $periods[] = ['id' => $period->id, 'name' => $period->name, 'grade' => $this->calc->display($periodGrades[$period->id]), 'challenges' => $details];
                }
                $rows[] = ['student_id' => $student->id, 'student' => $student->name, 'module' => $module->code, 'periods' => $periods, 'annual' => $this->calc->display($this->calc->mean($periodGrades))];
            }
        }

        return ['classroom' => $classroom, 'rows' => $rows];
    }
}
