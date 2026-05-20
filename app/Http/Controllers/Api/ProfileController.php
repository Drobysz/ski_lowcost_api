<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): ClientResource
    {
        /** @var Client $client */
        $client = $request->user();

        return new ClientResource($client->load(['reservations.accommodations.room.images', 'accommodations.room.images']));
    }

    public function update(UpdateClientRequest $request): ClientResource
    {
        /** @var Client $client */
        $client = $request->user();
        $client->update($request->validated());

        return new ClientResource($client->refresh());
    }
}
