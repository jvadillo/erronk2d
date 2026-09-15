<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->dropUnique(['academic_year_id', 'position']);
            $table->foreignId('classroom_id')->nullable()->constrained()->restrictOnDelete();
        });

        foreach (DB::table('periods')->orderBy('id')->get() as $period) {
            foreach (DB::table('classrooms')->where('academic_year_id', $period->academic_year_id)->orderBy('id')->get() as $classroom) {
                $periodId = DB::table('periods')->insertGetId([
                    'academic_year_id' => $period->academic_year_id,
                    'classroom_id' => $classroom->id,
                    'name' => $period->name,
                    'position' => $period->position,
                    'created_at' => $period->created_at,
                    'updated_at' => $period->updated_at,
                ]);
                DB::table('challenges')->where('classroom_id', $classroom->id)->where('period_id', $period->id)->update(['period_id' => $periodId]);
            }
            DB::table('periods')->where('id', $period->id)->delete();
        }

        Schema::table('periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->unsignedBigInteger('classroom_id')->nullable(false)->change();
            $table->unique(['classroom_id', 'position']);
        });
        Schema::table('classroom_module', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Las evaluaciones independientes de cada grupo no pueden fusionarse sin perder información. Restaura la copia previa al despliegue.');
    }
};
