<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['name' => 'admin'],
            ['password' => Hash::make('password123')],
        );

        Admin::query()->updateOrCreate(
            ['name' => 'manager'],
            ['password' => Hash::make('password123')],
        );
    }
}
