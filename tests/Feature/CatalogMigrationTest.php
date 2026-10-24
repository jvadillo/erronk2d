<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogMigrationTest extends TestCase
{
    public function test_migration_archives_module_responsibilities_and_normalizes_legacy_catalog_without_losing_data(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.catalog_migration' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('catalog_migration');
        try {
            $migrationPath = database_path('migrations/2026_10_25_100000_open_catalog_and_retire_module_responsibilities.php');
            foreach (glob(database_path('migrations/*.php')) as $path) {
                if ($path !== $migrationPath) {
                    (require $path)->up();
                }
            }
            $admin = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'unused-test-value', 'role' => 'admin']);
            $teacher = DB::table('users')->insertGetId(['name' => 'Docente', 'email' => 'teacher@example.test', 'password' => 'unused-test-value', 'role' => 'teacher']);
            $year = DB::table('academic_years')->insertGetId(['name' => '2026-2027']);
            DB::table('academic_years')->insert(['name' => '2026 - 2027']);
            $cycle = DB::table('cycles')->insertGetId(['name' => 'Desarrollo Web', 'code' => 'DAW']);
            $twin = DB::table('cycles')->insertGetId(['name' => 'desarrollo web', 'code' => 'daw']);
            $module = DB::table('modules')->insertGetId(['cycle_id' => $cycle, 'level' => 1, 'name' => 'Programación', 'code' => 'PROG']);
            DB::table('modules')->insert(['cycle_id' => $cycle, 'level' => 2, 'name' => 'Programación', 'code' => 'PROG']);
            $class = DB::table('classrooms')->insertGetId(['academic_year_id' => $year, 'name' => 'Grupo', 'cycle_id' => $cycle, 'level' => 1, 'owner_id' => $teacher]);
            DB::table('classroom_user')->insert(['classroom_id' => $class, 'user_id' => $teacher]);
            DB::table('classroom_module')->insert(['classroom_id' => $class, 'module_id' => $module, 'name' => 'Programación', 'code' => 'PROG']);
            DB::table('classroom_module_user')->insert(['classroom_id' => $class, 'module_id' => $module, 'user_id' => $teacher]);
            $kept = fn () => [DB::table('users')->count(), DB::table('classroom_user')->get()->all(), DB::table('classroom_module')->get()->all(), DB::table('academic_years')->count(), DB::table('cycles')->count(), DB::table('modules')->count()];
            $before = $kept();

            $migration = require $migrationPath;
            $migration->up();

            $this->assertFalse(Schema::hasTable('classroom_module_user'));
            $this->assertEquals($before, $kept());
            $archive = DB::table('audit_events')->where('action', 'system.module_responsibilities_retired')->sole();
            $this->assertSame([$admin, [['classroom_id' => $class, 'module_id' => $module, 'user_id' => $teacher]]], [$archive->user_id, json_decode($archive->before, true)]);
            $this->assertSame(['daw', 'desarrollo web'], [DB::table('cycles')->where('id', $cycle)->value('code_key'), DB::table('cycles')->where('id', $cycle)->value('name_key')]);
            $this->assertStringEndsWith('#'.$twin, DB::table('cycles')->where('id', $twin)->value('code_key'));
            $this->assertSame(['2026-2027', '2026-2027#2'], DB::table('academic_years')->orderBy('id')->pluck('name_key')->all());

            $migration->down();

            $this->assertSame([['classroom_id' => $class, 'module_id' => $module, 'user_id' => $teacher]], DB::table('classroom_module_user')->get()->map(fn ($row) => (array) $row)->all());
            $this->assertFalse(Schema::hasColumn('cycles', 'code_key'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('catalog_migration');
        }
    }
}
