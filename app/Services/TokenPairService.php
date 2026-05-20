<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Client;
use App\Models\RefreshToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TokenPairService
{
    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function issue(Client|Admin $tokenable): array
    {
        $ability = $tokenable instanceof Admin ? 'admin' : 'client';

        $accessToken = $tokenable->createToken(
            name: $ability.'-access',
            abilities: [$ability],
            expiresAt: now()->addHour(),
        )->plainTextToken;

        $refreshToken = Str::random(80);

        RefreshToken::create([
            'tokenable_type' => $tokenable::class,
            'tokenable_id' => $tokenable->getKey(),
            'token_hash' => $this->hash($refreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function rotate(string $refreshToken, string $tokenableClass): array
    {
        return DB::transaction(function () use ($refreshToken, $tokenableClass): array {
            $storedRefreshToken = RefreshToken::query()
                ->where('token_hash', $this->hash($refreshToken))
                ->where('tokenable_type', $tokenableClass)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $storedRefreshToken instanceof RefreshToken) {
                throw ValidationException::withMessages([
                    'refresh_token' => ['The refresh token is invalid or expired.'],
                ]);
            }

            $storedRefreshToken->update(['revoked_at' => now()]);

            /** @var Client|Admin|null $tokenable */
            $tokenable = $storedRefreshToken->tokenable;

            if (! $tokenable instanceof Model) {
                throw ValidationException::withMessages([
                    'refresh_token' => ['The refresh token owner no longer exists.'],
                ]);
            }

            return $this->issue($tokenable);
        });
    }

    public function revokeRefreshTokens(Client|Admin $tokenable): void
    {
        RefreshToken::query()
            ->where('tokenable_type', $tokenable::class)
            ->where('tokenable_id', $tokenable->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
