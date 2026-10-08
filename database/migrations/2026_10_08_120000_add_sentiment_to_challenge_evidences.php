<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenge_evidences', function (Blueprint $table) {
            $table->string('sentiment', 10)->default('neutral')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('challenge_evidences', function (Blueprint $table) {
            $table->dropColumn('sentiment');
        });
    }
};
