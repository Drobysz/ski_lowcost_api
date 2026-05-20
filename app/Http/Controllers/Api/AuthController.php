<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\TokenPairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly TokenPairService $tokens) {}

    public function register(RegisterClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json([
            'data' => [
                'id' => $client->id,
            ],
            'message' => 'Client registered successfully',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $client = Client::query()->where('tel', $request->string('tel')->toString())->first();

        if (! $client instanceof Client || ! Hash::check($request->string('password')->toString(), $client->password)) {
            throw ValidationException::withMessages([
                'tel' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json($this->tokens->issue($client));
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        return response()->json(
            $this->tokens->rotate($request->string('refresh_token')->toString(), Client::class),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $client->currentAccessToken()?->delete();
        $this->tokens->revokeRefreshTokens($client);

        return response()->json(['ok' => true]);
    }

    public function profile(Request $request): ClientResource
    {
        /** @var Client $client */
        $client = $request->user();

        return new ClientResource($client->load(['reservations.accommodations.room.images', 'accommodations.room.images']));
    }
}
