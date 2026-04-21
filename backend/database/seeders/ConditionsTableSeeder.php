<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConditionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('conditions')->insert([
            ['name' => '良好'],
            ['name' => '目立った傷や汚れなし'],
            ['name' => 'やや傷や汚れあり'],
            ['name' => '状態が悪い'],
        ]);
    }
}
