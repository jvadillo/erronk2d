<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ARCHIVE_ACTION = 'system.module_responsibilities_retired';

    /**
     * Adds normalized keys that reject duplicated catalog entries (also under concurrent creation)
     * and retires module responsibilities, archiving their rows in the audit log.
     */
    public function up(): void
    {
        Schema::table('academic_years', fn (Blueprint $table) => $table->string('name_key')->nullable());
        Schema::table('cycles', function (Blueprint $table) {
            $table->string('name_key', 150)->nullable();
            $table->string('code_key', 30)->nullable();
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->string('name_key', 150)->nullable();
            $table->string('code_key', 30)->nullable();
        });
        $this->fillKeys('academic_years', ['name_key' => 'name']);
        $this->fillKeys('cycles', ['name_key' => 'name', 'code_key' => 'code']);
        $this->fillKeys('modules', ['name_key' => 'name', 'code_key' => 'code'], 'cycle_id');
        Schema::table('academic_years', fn (Blueprint $table) => $table->unique('name_key'));
        Schema::table('cycles', function (Blueprint $table) {
            $table->unique('name_key');
            $table->unique('code_key');
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->unique(['cycle_id', 'name_key']);
            $table->unique(['cycle_id', 'code_key']);
        });

        if (! Schema::hasTable('classroom_module_user')) {
            return;
        }
        $rows = DB::table('classroom_module_user')->orderBy('classroom_id')->orderBy('module_id')->orderBy('user_id')->get(['classroom_id', 'module_id', 'user_id']);
        if ($rows->isNotEmpty()) {
            $author = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id') ?? $rows->first()->user_id;
            DB::table('audit_events')->insert([
                'user_id' => $author, 'action' => self::ARCHIVE_ACTION,
                'before' => json_encode($rows->map(fn ($row) => (array) $row)->all()), 'after' => json_encode([]),
                'reason' => 'La pertenencia al grupo sustituye a los responsables de módulo.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::drop('classroom_module_user');
    }

    public function down(): void
    {
        Schema::create('classroom_module_user', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['classroom_id', 'module_id', 'user_id']);
            $table->foreign(['classroom_id', 'module_id'])->references(['classroom_id', 'module_id'])->on('classroom_module')->cascadeOnDelete();
            $table->foreign(['classroom_id', 'user_id'])->references(['classroom_id', 'user_id'])->on('classroom_user')->cascadeOnDelete();
        });
        $archive = DB::table('audit_events')->where('action', self::ARCHIVE_ACTION)->latest('id')->value('before');
        foreach (json_decode($archive ?? '[]', true) as $row) {
            $stillValid = DB::table('classroom_module')->where('classroom_id', $row['classroom_id'])->where('module_id', $row['module_id'])->exists()
                && DB::table('classroom_user')->where('classroom_id', $row['classroom_id'])->where('user_id', $row['user_id'])->exists();
            if ($stillValid) {
                DB::table('classroom_module_user')->insert($row);
            }
        }
        Schema::table('modules', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'name_key']);
            $table->dropUnique(['cycle_id', 'code_key']);
            $table->dropColumn(['name_key', 'code_key']);
        });
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropUnique(['name_key']);
            $table->dropUnique(['code_key']);
            $table->dropColumn(['name_key', 'code_key']);
        });
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropUnique(['name_key']);
            $table->dropColumn('name_key');
        });
    }

    /**
     * Stores the normalized value of each column. Legacy rows that already collide keep a key
     * suffixed with their id, so the migration never merges or deletes existing data.
     *
     * @param  array<string, string>  $columns  key column => source column
     */
    private function fillKeys(string $table, array $columns, ?string $scope = null): void
    {
        $used = [];
        foreach (DB::table($table)->orderBy('id')->get() as $row) {
            $values = [];
            foreach ($columns as $key => $source) {
                $value = $this->normalize((string) $row->$source);
                $bucket = $key.'|'.($scope ? ($row->$scope ?? 'none-'.$row->id) : '');
                if (isset($used[$bucket][$value])) {
                    $value = mb_substr($value, 0, 20).'#'.$row->id;
                }
                $used[$bucket][$value] = true;
                $values[$key] = $value;
            }
            DB::table($table)->where('id', $row->id)->update($values);
        }
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(preg_replace(['/\s+/u', '/\s*([\/-])\s*/u'], [' ', '$1'], trim($value)));
    }
};
