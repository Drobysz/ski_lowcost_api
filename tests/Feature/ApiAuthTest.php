<?php

use App\Models\Accommodation;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

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
    $this->getJson('/api/rooms/my')->assertUnauthorized();
    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
    ])->assertUnauthorized();
});

test('authenticated client can list own rooms with reservation data', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $room = Room::factory()->create(['num' => 101]);
    $secondRoom = Room::factory()->create(['num' => 102]);
    $otherRoom = Room::factory()->create(['num' => 201]);

    $reservation = Reservation::query()->create([
        'client_id' => $client->id,
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
        'status' => 'paid',
        'total_price' => 840,
    ]);
    $otherReservation = Reservation::query()->create([
        'client_id' => $otherClient->id,
        'check_in' => '2026-02-10 15:00:00',
        'check_out' => '2026-02-17 10:00:00',
        'status' => 'paid',
        'total_price' => 420,
    ]);

    Accommodation::query()->create([
        'reservation_id' => $reservation->id,
        'room_id' => $room->id,
        'client_id' => $client->id,
    ]);
    Accommodation::query()->create([
        'reservation_id' => $reservation->id,
        'room_id' => $secondRoom->id,
        'client_id' => $client->id,
    ]);
    Accommodation::query()->create([
        'reservation_id' => $otherReservation->id,
        'room_id' => $otherRoom->id,
        'client_id' => $otherClient->id,
    ]);

    Sanctum::actingAs($client, ['client']);

    $this->getJson('/api/rooms/my')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.reservation.id', $reservation->id)
        ->assertJsonPath('data.0.reservation.check_in', '2026-01-10 15:00:00')
        ->assertJsonPath('data.0.reservation.check_out', '2026-01-17 10:00:00')
        ->assertJsonPath('data.0.reservation.status', 'paid')
        ->assertJsonMissing(['id' => $otherRoom->id]);
});

test('client room index is paginated by six rooms', function () {
    $client = Client::factory()->create();
    Room::factory()->count(7)->create();

    Sanctum::actingAs($client, ['client']);

    $this->getJson('/api/rooms')
        ->assertSuccessful()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('meta.per_page', 6)
        ->assertJsonPath('meta.total', 7);
});

test('admin room index keeps default pagination', function () {
    $admin = Admin::query()->create([
        'name' => 'admin',
        'password' => Hash::make('password123'),
    ]);
    Room::factory()->count(16)->create();

    Sanctum::actingAs($admin, ['admin']);

    $this->getJson('/api/admin/rooms')
        ->assertSuccessful()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 16);
});

test('authenticated client can list rooms available for date range', function () {
    $client = Client::factory()->create();
    $availableRoom = Room::factory()->create(['num' => 301]);
    $occupiedRoom = Room::factory()->create(['num' => 302]);
    $cancelledRoom = Room::factory()->create(['num' => 303]);
    $outsideRangeRoom = Room::factory()->create(['num' => 304]);

    $occupiedReservation = Reservation::query()->create([
        'client_id' => $client->id,
        'check_in' => '2026-01-12 15:00:00',
        'check_out' => '2026-01-15 10:00:00',
        'status' => 'paid',
    ]);
    $cancelledReservation = Reservation::query()->create([
        'client_id' => $client->id,
        'check_in' => '2026-01-12 15:00:00',
        'check_out' => '2026-01-15 10:00:00',
        'status' => 'cancelled',
    ]);
    $outsideReservation = Reservation::query()->create([
        'client_id' => $client->id,
        'check_in' => '2026-02-01 15:00:00',
        'check_out' => '2026-02-08 10:00:00',
        'status' => 'paid',
    ]);

    Accommodation::query()->create([
        'reservation_id' => $occupiedReservation->id,
        'room_id' => $occupiedRoom->id,
        'client_id' => $client->id,
    ]);
    Accommodation::query()->create([
        'reservation_id' => $cancelledReservation->id,
        'room_id' => $cancelledRoom->id,
        'client_id' => $client->id,
    ]);
    Accommodation::query()->create([
        'reservation_id' => $outsideReservation->id,
        'room_id' => $outsideRangeRoom->id,
        'client_id' => $client->id,
    ]);

    Sanctum::actingAs($client, ['client']);

    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
    ])
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $availableRoom->id])
        ->assertJsonFragment(['id' => $cancelledRoom->id])
        ->assertJsonFragment(['id' => $outsideRangeRoom->id])
        ->assertJsonMissing(['id' => $occupiedRoom->id]);
});

test('available rooms request validates date range', function () {
    $client = Client::factory()->create();

    Sanctum::actingAs($client, ['client']);

    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-17 10:00:00',
        'check_out' => '2026-01-10 15:00:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['check_out']);
});
