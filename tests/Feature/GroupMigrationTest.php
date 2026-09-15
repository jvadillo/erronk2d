<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GroupMigrationTest extends TestCase
{
    public function test_migration_copies_year_evaluations_to_each_group_and_preserves_challenges_and_publications(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.group_migration' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('group_migration');
        try {
            $migrationPath = database_path('migrations/2026_09_15_111938_move_periods_to_classrooms.php');
            foreach (glob(database_path('migrations/*.php')) as $path) {
                if ($path !== $migrationPath) {
                    (require $path)->up();
                }
            }
            $year = DB::table('academic_years')->insertGetId(['name' => 'Anterior', 'is_open' => false]);
            $emptyYear = DB::table('academic_years')->insertGetId(['name' => 'Sin grupos']);
            $first = DB::table('periods')->insertGetId(['academic_year_id' => $year, 'name' => 'Primera', 'position' => 1]);
            DB::table('periods')->insert(['academic_year_id' => $year, 'name' => 'Segunda', 'position' => 2]);
            DB::table('periods')->insert(['academic_year_id' => $emptyYear, 'name' => 'Sin actividad', 'position' => 1]);
            $admin = DB::table('users')->insertGetId(['name' => 'Admin de prueba', 'email' => 'migration@example.test', 'password' => 'unused-test-value']);
            $groups = [];
            $challenges = [];
            foreach (['A', 'B'] as $name) {
                $groups[] = $group = DB::table('classrooms')->insertGetId(['academic_year_id' => $year, 'name' => $name]);
                $challenges[] = DB::table('challenges')->insertGetId(['classroom_id' => $group, 'period_id' => $first, 'name' => 'Reto '.$name, 'component_weights' => '{}', 'transversal_weights' => '{}', 'team_rubric' => '{}', 'transversal_rubric' => '{}', 'catalog_snapshot' => '{"period":"Primera"}']);
            }
            DB::table('publications')->insert(['challenge_id' => $challenges[0], 'published_by' => $admin, 'version' => 1, 'snapshot' => '{"historical":"unchanged"}']);
            $before = DB::table('challenges')->orderBy('id')->get()->map(fn ($challenge) => collect($challenge)->except('period_id')->all())->all();
            $publication = DB::table('publications')->first();

            (require $migrationPath)->up();

            $this->assertSame(4, DB::table('periods')->count());
            $this->assertFalse(Schema::hasColumn('periods', 'academic_year_id'));
            foreach ($groups as $index => $group) {
                $periods = DB::table('periods')->where('classroom_id', $group)->orderBy('position')->get();
                $this->assertSame(['Primera', 'Segunda'], $periods->pluck('name')->all());
                $this->assertSame($periods[0]->id, DB::table('challenges')->where('id', $challenges[$index])->value('period_id'));
            }
            $this->assertSame($before, DB::table('challenges')->orderBy('id')->get()->map(fn ($challenge) => collect($challenge)->except('period_id')->all())->all());
            $this->assertSame((array) $publication, (array) DB::table('publications')->first());
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('group_migration');
        }
    }
}
