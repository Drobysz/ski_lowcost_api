<?php

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a client can register and login with token pair', function () {
    $payload = [
        'first_name' => 'Test',
        'last_name' => 'Client',
        'age' => 30,
        'address' => '1 Rue des Alpes, Paris',
        'birth_date' => '1996-01-01',
        'tel' => '+33000000001',
        'skiing_level' => 'medium',
        'height' => 1.78,
        'weight' => 74,
        'shoe_size' => 42,
        'password' => 'password123',
    ];

    $this->postJson('/api/clients', $payload)
        ->assertCreated()
        ->assertJsonPath('data.id', 1);

    $this->postJson('/api/auth/login', [
        'tel' => '+33000000001',
        'password' => 'password123',
    ])
        ->assertSuccessful()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});

test('an admin can login with token pair', function () {
    Admin::query()->create([
        'name' => 'admin',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/admin/login', [
        'name' => 'admin',
        'password' => 'password123',
    ])
        ->assertSuccessful()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});

test('authenticated client routes require a client bearer token', function () {
    Client::query()->create([
        'first_name' => 'Test',
        'last_name' => 'Client',
        'age' => 30,
        'address' => '1 Rue des Alpes, Paris',
        'birth_date' => '1996-01-01',
        'tel' => '+33000000001',
        'skiing_level' => 'medium',
        'height' => 1.78,
        'weight' => 74,
        'shoe_size' => 42,
        'password' => Hash::make('password123'),
    ]);

    $this->getJson('/api/rooms')->assertUnauthorized();
});
