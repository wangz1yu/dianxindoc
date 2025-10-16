<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

abstract class BaseSeeder extends Seeder
{
    public function runOnce(callable $callback)
    {
        if (!DB::table('seed_history')->where('seeder_class', static::class)->exists()) {
            $callback();
            DB::table('seed_history')->insert([
                'seeder_class' => static::class,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
