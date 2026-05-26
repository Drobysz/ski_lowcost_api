<?php

use App\Http\Controllers\Api\AccommodationController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomImageController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/refresh', [AdminAuthController::class, 'refresh']);

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::get('/room-images/{image}', [RoomImageController::class, 'show']);

Route::middleware(['auth:sanctum', 'client.token'])->get('/rooms/my', [RoomController::class, 'my']);
Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
Route::post('/rooms/available', [RoomController::class, 'available']);

Route::middleware(['auth:sanctum', 'client.token'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/users', [ClientController::class, 'users']);

    Route::apiResource('clients', ClientController::class)->except(['store']);
    Route::apiResource('reservations', ReservationController::class);
    Route::apiResource('accommodations', AccommodationController::class);

    Route::post('/stripe/checkout', [StripeController::class, 'checkout']);
});

Route::middleware(['auth:sanctum', 'admin.token'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    Route::apiResource('admins', AdminController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('rooms', RoomController::class);
    Route::apiResource('reservations', ReservationController::class);
    Route::apiResource('accommodations', AccommodationController::class);
});
