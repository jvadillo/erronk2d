<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE users ADD COLUMN classroom_id INTEGER REFERENCES classrooms(id) ON DELETE RESTRICT');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->restrictOnDelete();
            });
        }
        foreach (['users', 'academic_years', 'classrooms', 'modules', 'rubrics'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('demo_key')->nullable()->unique();
            });
        }
        $enrollments = DB::table('classroom_user')->select('user_id')->selectRaw('MIN(classroom_id) AS classroom_id')
            ->groupBy('user_id')->havingRaw('COUNT(*) = 1')->get();
        foreach ($enrollments as $enrollment) {
            DB::table('users')->where('id', $enrollment->user_id)->where('role', 'student')->update(['classroom_id' => $enrollment->classroom_id]);
        }
        // Legacy links remain available for resolving ambiguous enrollments; current membership uses users.classroom_id.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE users DROP COLUMN classroom_id');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('classroom_id');
            });
        }
        foreach (['users', 'academic_years', 'classrooms', 'modules', 'rubrics'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['demo_key']);
                $table->dropColumn('demo_key');
            });
        }
    }
};
