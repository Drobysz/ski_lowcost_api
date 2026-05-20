<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Admin;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->user() instanceof Admin) {
            return ClientResource::collection(Client::query()->latest('id')->paginate());
        }

        /** @var Client $client */
        $client = $request->user();

        return ClientResource::collection(Client::query()->whereKey($client->getKey())->paginate());
    }

    public function store(RegisterClientRequest $request): ClientResource
    {
        return new ClientResource(Client::create($request->validated()));
    }

    public function show(Request $request, Client $client): ClientResource
    {
        $this->authorizeClientAccess($request, $client);

        return new ClientResource($client->load(['reservations.accommodations.room.images', 'accommodations.room.images']));
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $this->authorizeClientAccess($request, $client);

        $client->update($request->validated());

        return new ClientResource($client->refresh());
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($request, $client);

        $client->delete();

        return response()->json(null, 204);
    }

    private function authorizeClientAccess(Request $request, Client $client): void
    {
        $user = $request->user();

        abort_unless($user instanceof Admin || ($user instanceof Client && $user->is($client)), 403);
    }
}
