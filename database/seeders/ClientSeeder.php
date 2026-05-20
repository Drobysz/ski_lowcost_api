<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::query()->updateOrCreate(
            ['tel' => '+33000000001'],
            [
                'first_name' => 'Test',
                'last_name' => 'Client',
                'age' => 30,
                'address' => '1 Rue des Alpes, Paris',
                'birth_date' => '1996-01-01',
                'skiing_level' => 'medium',
                'height' => 1.78,
                'weight' => 74,
                'shoe_size' => 42,
                'password' => Hash::make('password123'),
                'role' => 'client',
            ],
        );

        Client::query()->updateOrCreate(
            ['tel' => '+33000000002'],
            [
                'first_name' => 'Second',
                'last_name' => 'Client',
                'age' => 27,
                'address' => '2 Rue des Alpes, Lyon',
                'birth_date' => '1999-02-02',
                'skiing_level' => 'beginner',
                'height' => 1.68,
                'weight' => 62,
                'shoe_size' => 39,
                'password' => Hash::make('password123'),
                'role' => 'client',
            ],
        );
    }
}
