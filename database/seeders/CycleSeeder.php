<?php

namespace Database\Seeders;

use App\Models\Cycle;
use Illuminate\Database\Seeder;

class CycleSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        Cycle::firstOrCreate(['code' => 'DAW'], ['name' => 'Desarrollo de Aplicaciones Web']);
    }
}
