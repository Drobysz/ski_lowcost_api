<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! $this->hasValidSignature($payload, (string) $request->header('Stripe-Signature'))) {
            return response()->json(['message' => 'Invalid Stripe signature.'], 400);
        }

        $event = json_decode($payload, true);

        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $sessionId = $event['data']['object']['id'] ?? null;

            if (is_string($sessionId)) {
                DB::transaction(function () use ($sessionId): void {
                    $reservation = Reservation::query()
                        ->where('stripe_session_id', $sessionId)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation instanceof Reservation) {
                        $reservation->update([
                            'status' => 'paid',
                            'purchase_date' => now(),
                            'paid_at' => now(),
                        ]);
                    }
                });
            }
        }

        return response()->json(['ok' => true]);
    }

    private function hasValidSignature(string $payload, string $signatureHeader): bool
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return true;
        }

        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! is_string($timestamp) || ! is_string($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
