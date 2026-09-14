<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->boolean('is_open')->default(true);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('last_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
        });
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('code', 30)->unique();
            $table->timestamps();
        });
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('level')->default(1);
            $table->string('cycle_name', 150)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable()->change();
            $table->foreignId('cycle_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('level')->default(1);
            $table->unique(['cycle_id', 'level', 'code']);
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->unique(['classroom_id', 'student_id']);
        });
        Schema::create('classroom_module', function (Blueprint $table) {
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 30);
            $table->primary(['classroom_id', 'module_id']);
        });
        Schema::create('classroom_module_user', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['classroom_id', 'module_id', 'user_id']);
            $table->foreign(['classroom_id', 'module_id'])->references(['classroom_id', 'module_id'])->on('classroom_module')->cascadeOnDelete();
            $table->foreign(['classroom_id', 'user_id'])->references(['classroom_id', 'user_id'])->on('classroom_user')->cascadeOnDelete();
        });
        Schema::table('rubrics', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('cycle_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('level')->nullable();
        });
        Schema::create('rubric_user', function (Blueprint $table) {
            $table->foreignId('rubric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['rubric_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_user');
        Schema::table('rubrics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('cycle_id');
            $table->dropColumn('level');
        });
        Schema::dropIfExists('classroom_module_user');
        Schema::dropIfExists('classroom_module');
        Schema::dropIfExists('enrollments');
        Schema::table('modules', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'level', 'code']);
            $table->dropConstrainedForeignId('cycle_id');
            $table->dropColumn('level');
        });
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cycle_id');
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['level', 'cycle_name']);
        });
        Schema::dropIfExists('cycles');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('last_academic_year_id'));
        Schema::table('academic_years', fn (Blueprint $table) => $table->dropColumn('is_open'));
    }
};
