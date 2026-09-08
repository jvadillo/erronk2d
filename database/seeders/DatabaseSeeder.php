<?php

namespace Database\Seeders;

use App\Domain\Grades\Gradebook;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\ModuleGrade;
use App\Models\Publication;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Los datos de demostración solo se permiten en local o testing.');
        }
        $password = env('ERRONK2D_DEMO_PASSWORD');
        if (! $password || strlen($password) < 12) {
            throw new RuntimeException('Configura ERRONK2D_DEMO_PASSWORD con al menos 12 caracteres.');
        }
        if (User::exists() || AcademicYear::exists()) {
            throw new RuntimeException('La demostración requiere una base de datos vacía; no se sobrescribirán datos.');
        }
        DB::transaction(function () use ($password) {
            $admin = User::create(['name' => 'Leire Aranburu', 'email' => 'admin@erronk2d.test', 'password' => $password, 'role' => 'admin']);
            $teachers = [];
            foreach (['Ane Etxeberria', 'Mikel Otxoa', 'Nerea Zubia', 'Iker Aguirre'] as $i => $name) {
                $teachers[] = User::create(['name' => $name, 'email' => 'profesor'.($i + 1).'@erronk2d.test', 'password' => $password, 'role' => 'teacher', 'permissions' => User::PERMISSIONS]);
            }
            $year = AcademicYear::create(['name' => '2026-2027']);
            $periods = [];
            foreach (['1.ª Evaluación', '2.ª Evaluación', '3.ª Evaluación'] as $i => $name) {
                $periods[] = $year->periods()->create(['name' => $name, 'position' => $i + 1]);
            }
            $class = Classroom::create(['academic_year_id' => $year->id, 'name' => '2DAW-A']);
            $students = [];
            $names = ['Ainhoa Agirre', 'Aitor Fernández', 'Alaia Martínez', 'Ander García', 'Ane Lertxundi', 'Asier Ibáñez', 'Danel Ortiz', 'Eider Pérez', 'Ekain Gómez', 'Elene Ruiz', 'Enara Sánchez', 'Gorka Martín', 'Haizea López', 'Iker Rodríguez', 'Irati Etxeberria', 'June Alonso', 'Lander Bilbao', 'Maialen Álvarez', 'Nora Urrutia', 'Unai Romero'];
            foreach ($names as $i => $name) {
                $students[] = User::create(['name' => $name, 'email' => 'alumno'.($i + 1).'@erronk2d.test', 'password' => $password, 'role' => 'student']);
            }
            $class->users()->attach(array_map(fn ($u) => $u->id, [...$students, ...$teachers]));
            $modules = [];
            foreach ([['PROG', 'Programación'], ['DWEC', 'Desarrollo web en entorno cliente'], ['DWES', 'Desarrollo web en entorno servidor'], ['DIW', 'Diseño de interfaces web']] as $i => [$code,$name]) {
                $m = Module::create(['classroom_id' => $class->id, 'name' => $name, 'code' => $code]);
                $m->teachers()->attach($teachers[$i]);
                $modules[] = $m;
            }
            $modules[0]->teachers()->attach($teachers[1]);
            $levels = [['score' => '4', 'description' => 'Necesita acompañamiento para avanzar.'], ['score' => '6', 'description' => 'Resuelve las tareas básicas con apoyo puntual.'], ['score' => '8', 'description' => 'Trabaja con autonomía y cumple los objetivos.'], ['score' => '10', 'description' => 'Supera los objetivos y aporta mejoras al equipo.']];
            $teamItems = [];
            foreach (['Calidad del código', 'Experiencia de usuario', 'Arquitectura del servidor', 'Diseño y accesibilidad', 'Presentación y documentación'] as $i => $name) {
                $teamItems[] = ['key' => 'team_'.$i, 'name' => $name, 'description' => 'Valora las evidencias del trabajo presentado.', 'weight' => '1', 'module_id' => $modules[$i]->id ?? null, 'levels' => $levels];
            }
            $transItems = [];
            foreach (['Autonomía', 'Trabajo en equipo', 'Implicación', 'Responsabilidad'] as $i => $name) {
                $transItems[] = ['key' => 'trans_'.$i, 'name' => $name, 'description' => 'Reflexiona sobre la participación durante el reto.', 'weight' => '1', 'module_id' => null, 'levels' => $levels];
            }
            $tr = Rubric::create(['name' => 'Proyecto web · Rúbrica de equipo', 'kind' => 'team', 'items' => $teamItems]);
            $xr = Rubric::create(['name' => 'Aprender en equipo · Transversales', 'kind' => 'transversal', 'items' => $transItems]);
            Rubric::create(['name' => 'Presentación de un prototipo', 'kind' => 'team', 'items' => [...array_map(fn ($item) => [...$item, 'module_id' => null], array_slice($teamItems, 0, 2))]]);
            $titles = ['Una web para nuestra comunidad', 'Datos que cuentan historias', 'Un comercio más cercano', 'Conectamos el barrio', 'Ideas con impacto', 'Nuestro portfolio profesional'];
            $descriptions = ['Diseñamos una plataforma accesible para conectar las iniciativas de nuestro entorno.', 'Transformamos datos abiertos en una experiencia interactiva, útil y comprensible.', 'Un escaparate digital para impulsar el pequeño comercio de nuestra ciudad.', 'Una aplicación para compartir recursos y fortalecer la comunidad.', 'Convertimos una necesidad real en un producto digital que aporta valor.', 'Mostramos lo aprendido en un portfolio que cuenta nuestra historia.'];
            foreach ($titles as $ci => $title) {
                $ch = Challenge::create(['name' => $title, 'description' => $descriptions[$ci], 'classroom_id' => $class->id, 'period_id' => $periods[intdiv($ci, 2)]->id, 'status' => $ci === 5 ? 'draft' : ($ci === 4 ? 'active' : 'evaluating'), 'weight' => $ci % 2 === 0 ? '2' : '3', 'distribution_enabled' => $ci !== 2, 'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30], 'team_rubric' => ['name' => $tr->name, 'items' => $tr->items], 'transversal_rubric' => ['name' => $xr->name, 'items' => $xr->items], 'starts_at' => now()->addDays($ci * 14)->toDateString(), 'ends_at' => now()->addDays($ci * 14 + 12)->toDateString()]);
                $ch->modules()->attach(array_map(fn ($m) => $m->id, $modules));
                $ch->students()->attach(array_map(fn ($s) => $s->id, $students));
                if ($ci === 2) {
                    foreach ($modules as $m) {
                        $ch->modules()->updateExistingPivot($m->id, ['defense_enabled' => false]);
                    }
                }
                $rotated = [...array_slice($students, $ci * 2), ...array_slice($students, 0, $ci * 2)];
                foreach (array_chunk($rotated, 4) as $ti => $group) {
                    $team = $ch->teams()->create(['name' => ['Haritz', 'Itsaso', 'Mendi', 'Izar', 'Hodei'][$ti]]);
                    foreach ($group as $si => $s) {
                        $team->memberships()->create(['challenge_id' => $ch->id, 'student_id' => $s->id, 'allocation' => ($ci < 2 || ($ci === 3 && $ti < 3)) ? ['7', '8', '8', '9'][$si] : null]);
                    }
                    if ($ci >= 4) {
                        continue;
                    }
                    foreach ($teamItems as $item) {
                        Assessment::create(['challenge_id' => $ch->id, 'kind' => 'team', 'subject_id' => $team->id, 'scope_id' => 0, 'criterion' => $item['key'], 'level' => 2, 'updated_by' => $admin->id]);
                    }
                    foreach ($group as $si => $s) {
                        if ($ci === 3 && $ti === 4) {
                            continue;
                        }
                        foreach ($transItems as $item) {
                            foreach (['self', 'teacher'] as $kind) {
                                Assessment::create(['challenge_id' => $ch->id, 'kind' => $kind, 'subject_id' => $s->id, 'scope_id' => $kind === 'self' ? $s->id : 0, 'criterion' => $item['key'], 'level' => 2, 'updated_by' => $kind === 'self' ? $s->id : $teachers[0]->id]);
                            }
                            foreach ($group as $peer) {
                                if ($peer->id !== $s->id) {
                                    Assessment::create(['challenge_id' => $ch->id, 'kind' => 'peer', 'subject_id' => $s->id, 'scope_id' => $peer->id, 'criterion' => $item['key'], 'level' => 2, 'updated_by' => $peer->id]);
                                }
                            }
                        }
                        foreach ($modules as $mi => $m) {
                            ModuleGrade::create(['challenge_id' => $ch->id, 'module_id' => $m->id, 'student_id' => $s->id, 'exam' => ($ci === 3 && $si === 3) ? null : (string) (6 + ($si + $mi) % 4), 'defense' => $ci === 2 ? null : ['0.5', '-0.25', '0', '0.25'][$mi], 'defense_teacher_id' => $ci === 2 ? null : $teachers[$mi]->id, 'defense_date' => $ci === 2 ? null : now()->toDateString(), 'defense_notes' => $ci === 2 ? null : 'Explica y justifica su contribución al equipo.', 'updated_by' => $teachers[$mi]->id]);
                        }
                    }
                }
                if ($ci === 1) {
                    $book = app(Gradebook::class)->challenge($ch->fresh());
                    Publication::create(['challenge_id' => $ch->id, 'version' => 1, 'snapshot' => $book, 'published_by' => $admin->id]);
                    $ch->update(['status' => 'published']);
                }
            }
        });
    }
}
