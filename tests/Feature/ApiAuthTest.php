<?php

use App\Models\Accommodation;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Image;
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

    $this->getJson('/api/rooms/my')->assertUnauthorized();
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

test('reservation accommodations include full room data and images', function () {
    $client = Client::factory()->create();
    $room = Room::factory()->create([
        'num' => 405,
        'nb_lits' => 4,
        'view' => 'mountains',
    ]);
    $image = Image::query()->create([
        'room_id' => $room->id,
        'name' => 'suite.jpg',
        'path' => "rooms/{$room->id}/suite.jpg",
        'url' => null,
    ]);
    $reservation = Reservation::query()->create([
        'client_id' => $client->id,
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
        'status' => 'paid',
        'total_price' => 840,
    ]);

    Accommodation::query()->create([
        'reservation_id' => $reservation->id,
        'room_id' => $room->id,
        'client_id' => $client->id,
    ]);

    Sanctum::actingAs($client, ['client']);

    $this->getJson('/api/reservations')
        ->assertSuccessful()
        ->assertJsonPath('data.0.accommodations.0.room.id', $room->id)
        ->assertJsonPath('data.0.accommodations.0.room.num', 405)
        ->assertJsonPath('data.0.accommodations.0.room.nb_lits', 4)
        ->assertJsonPath('data.0.accommodations.0.room.view', 'mountains')
        ->assertJsonPath('data.0.accommodations.0.room.images.0.id', $image->id)
        ->assertJsonPath('data.0.accommodations.0.room.images.0.name', 'suite.jpg');
});

test('public room index is paginated by six rooms for clients', function () {
    Room::factory()->count(7)->create();

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
    Sanctum::actingAs(Client::factory()->create(), ['client']);

    $availableRoom = Room::factory()->create(['num' => 301]);
    $occupiedRoom = Room::factory()->create(['num' => 302]);
    $cancelledRoom = Room::factory()->create(['num' => 303]);
    $outsideRangeRoom = Room::factory()->create(['num' => 304]);
    $client = Client::factory()->create();

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

    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10',
        'check_out' => '2026-01-17',
    ])
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $availableRoom->id])
        ->assertJsonFragment(['id' => $cancelledRoom->id])
        ->assertJsonFragment(['id' => $outsideRangeRoom->id])
        ->assertJsonMissing(['id' => $occupiedRoom->id]);
});

test('available rooms can be filtered and sorted by beds', function () {
    Sanctum::actingAs(Client::factory()->create(), ['client']);

    $twoBedRoom = Room::factory()->create(['view' => 'mountains', 'nb_lits' => 2]);
    $fourBedRoom = Room::factory()->create(['view' => 'mountains', 'nb_lits' => 4]);
    $sixBedRoom = Room::factory()->create(['view' => 'mountains', 'nb_lits' => 6]);
    $parkingRoom = Room::factory()->create(['view' => 'parking', 'nb_lits' => 6]);

    $response = $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
        'filters' => [
            'view' => 'Slopes',
        ],
        'sort' => [
            'beds' => 'down',
        ],
    ])
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing(['id' => $parkingRoom->id]);

    expect(collect($response->json('data'))->pluck('nb_lits')->all())->toBe([6, 4, 2]);

    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
        'room_size' => 4,
    ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nb_lits', 4);

    $multiFilterResponse = $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-10 15:00:00',
        'check_out' => '2026-01-17 10:00:00',
        'filters' => [
            'view' => ['Slopes', 'Parking'],
            'room_size' => [2, 6],
        ],
        'sort' => [
            'beds' => 'up',
        ],
    ])
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment(['id' => $twoBedRoom->id])
        ->assertJsonFragment(['id' => $sixBedRoom->id])
        ->assertJsonFragment(['id' => $parkingRoom->id])
        ->assertJsonMissing(['id' => $fourBedRoom->id]);

    expect(collect($multiFilterResponse->json('data'))->pluck('nb_lits')->all())->toBe([2, 6, 6]);
});

test('available rooms request validates date range', function () {
    Sanctum::actingAs(Client::factory()->create(), ['client']);

    $this->postJson('/api/rooms/available', [
        'check_in' => '2026-01-17 10:00:00',
        'check_out' => '2026-01-10 15:00:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['check_out']);
});

test('authenticated client can search users by first and last name', function () {
    Sanctum::actingAs(Client::factory()->create(), ['client']);

    $matchingClient = Client::factory()->create([
        'first_name' => 'Elena',
        'last_name' => 'Rossi',
    ]);
    Client::factory()->create([
        'first_name' => 'Marcus',
        'last_name' => 'Thorne',
    ]);

    $this->getJson('/api/users?search=Elena')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingClient->id);

    $this->getJson('/api/users?search=elena%20rossi')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingClient->id);

    $this->getJson('/api/users')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});
