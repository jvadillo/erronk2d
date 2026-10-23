<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rubric_user');
    }

    public function down(): void
    {
        Schema::create('rubric_user', function (Blueprint $table) {
            $table->foreignId('rubric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['rubric_id', 'user_id']);
        });
    }
};
