<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->foreignId('team_rubric_id')->nullable()->after('team_rubric')->constrained('rubrics')->nullOnDelete();
            $table->foreignId('transversal_rubric_id')->nullable()->after('transversal_rubric')->constrained('rubrics')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_rubric_id');
            $table->dropConstrainedForeignId('transversal_rubric_id');
        });
    }
};
