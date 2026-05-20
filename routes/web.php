<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Zarza-Ski API',
        'ok' => true,
    ]);
});
