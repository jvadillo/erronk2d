<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each challenge lists its participating teachers. Existing challenges keep the access they had:
     * every active teacher of their group becomes a participant.
     */
    public function up(): void
    {
        Schema::create('challenge_teacher', function (Blueprint $table) {
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->primary(['challenge_id', 'user_id']);
        });
        $rows = DB::table('challenges')
            ->join('classroom_user', 'classroom_user.classroom_id', '=', 'challenges.classroom_id')
            ->join('users', 'users.id', '=', 'classroom_user.user_id')
            ->whereIn('users.role', ['teacher', 'admin'])->where('users.active', true)
            ->orderBy('challenges.id')->orderBy('users.id')
            ->get(['challenges.id as challenge_id', 'users.id as user_id']);
        foreach ($rows->chunk(500) as $chunk) {
            DB::table('challenge_teacher')->insert($chunk->map(fn ($row) => (array) $row)->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_teacher');
    }
};
