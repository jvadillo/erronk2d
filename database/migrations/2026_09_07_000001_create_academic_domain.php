<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('student');
            $t->json('permissions')->nullable();
            $t->boolean('active')->default(true);
        });
        Schema::create('academic_years', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('position');
            $t->unique(['academic_year_id', 'position']);
            $t->timestamps();
        });
        Schema::create('classrooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->unique(['academic_year_id', 'name']);
            $t->timestamps();
        });
        Schema::create('classroom_user', function (Blueprint $t) {
            $t->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->primary(['classroom_id', 'user_id']);
        });
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code', 30);
            $t->unique(['classroom_id', 'code']);
            $t->timestamps();
        });
        Schema::create('module_user', function (Blueprint $t) {
            $t->foreignId('module_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->primary(['module_id', 'user_id']);
        });
        Schema::create('rubrics', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind');
            $t->json('items');
            $t->timestamps();
        });
        Schema::create('challenges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $t->foreignId('period_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->text('notes')->nullable();
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->string('status')->default('draft');
            $t->decimal('weight', 10, 4)->default(1);
            $t->boolean('distribution_enabled')->default(true);
            $t->boolean('clamp_grade')->default(true);
            $t->json('component_weights');
            $t->json('transversal_weights');
            $t->json('team_rubric');
            $t->json('transversal_rubric');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('challenge_module', function (Blueprint $t) {
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->foreignId('module_id')->constrained()->restrictOnDelete();
            $t->boolean('defense_enabled')->default(true);
            $t->primary(['challenge_id', 'module_id']);
        });
        Schema::create('challenge_student', function (Blueprint $t) {
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->primary(['challenge_id', 'user_id']);
        });
        Schema::create('teams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unique(['challenge_id', 'name']);
            $t->unique(['id', 'challenge_id']);
            $t->timestamps();
        });
        Schema::create('memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('team_id');
            $t->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $t->foreign(['team_id', 'challenge_id'])->references(['id', 'challenge_id'])->on('teams')->cascadeOnDelete();
            $t->unique(['challenge_id', 'student_id']);
            $t->decimal('allocation', 8, 4)->nullable();
            $t->timestamps();
        });
        Schema::create('assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            // subject = team ID for team rubric, student ID otherwise. scope = evaluator for self/peer, zero for shared teacher assessment.
            $t->string('kind');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('scope_id')->default(0);
            $t->string('criterion');
            $t->unsignedSmallInteger('level');
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['challenge_id', 'kind', 'subject_id', 'scope_id', 'criterion'], 'assessment_identity');
        });
        Schema::create('module_grades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $t->foreignId('module_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $t->decimal('exam', 8, 4)->nullable();
            $t->decimal('defense', 8, 4)->nullable();
            $t->foreignId('defense_teacher_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('defense_date')->nullable();
            $t->text('defense_notes')->nullable();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->unique(['challenge_id', 'module_id', 'student_id']);
            $t->timestamps();
        });
        Schema::create('publications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('challenge_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->json('snapshot');
            $t->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $t->unique(['challenge_id', 'version']);
            $t->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('challenge_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action');
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['audit_events', 'publications', 'module_grades', 'assessments', 'memberships', 'teams', 'challenge_student', 'challenge_module', 'challenges', 'rubrics', 'module_user', 'modules', 'classroom_user', 'classrooms', 'periods', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'permissions', 'active']));
    }
};
