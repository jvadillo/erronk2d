<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', fn (Blueprint $table) => $table->json('catalog_snapshot')->nullable());
        Schema::table('module_grades', fn (Blueprint $table) => $table->boolean('not_enrolled')->default(false));
    }

    public function down(): void
    {
        Schema::table('module_grades', fn (Blueprint $table) => $table->dropColumn('not_enrolled'));
        Schema::table('challenges', fn (Blueprint $table) => $table->dropColumn('catalog_snapshot'));
    }
};
