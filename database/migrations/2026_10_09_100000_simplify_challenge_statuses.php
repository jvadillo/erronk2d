<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Removes the draft status: "En curso" now keeps student assessment closed
     * and "En evaluación" opens it, so open challenges keep their current access.
     */
    public function up(): void
    {
        DB::table('challenges')->where('status', 'active')->update(['status' => 'evaluating']);
        DB::table('challenges')->where('status', 'draft')->update(['status' => 'active']);
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
        DB::table('challenges')->where('status', 'active')->update(['status' => 'draft']);
    }
};
