<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\Admin;
use App\Services\TokenPairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function __construct(private readonly TokenPairService $tokens) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = Admin::query()->where('name', $request->string('name')->toString())->first();

        if (! $admin instanceof Admin || ! Hash::check($request->string('password')->toString(), $admin->password)) {
            throw ValidationException::withMessages([
                'name' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json($this->tokens->issue($admin));
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        return response()->json(
            $this->tokens->rotate($request->string('refresh_token')->toString(), Admin::class),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $admin->currentAccessToken()?->delete();
        $this->tokens->revokeRefreshTokens($admin);

        return response()->json(['ok' => true]);
    }
}
