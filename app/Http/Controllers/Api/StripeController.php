<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StripeCheckoutRequest;
use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StripeController extends Controller
{
    public function checkout(StripeCheckoutRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $data = $request->validated();

        $reservation = Reservation::query()
            ->withCount('accommodations')
            ->findOrFail($data['reservation_id']);

        abort_unless((int) $reservation->client_id === (int) $client->getKey(), 403);

        $secret = config('services.stripe.secret');
        abort_unless(is_string($secret) && $secret !== '', 422, 'Stripe secret is not configured.');
        $frontendUrl = $this->frontendUrl($request);

        $response = Http::asForm()
            ->withToken($secret)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $frontendUrl.'/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $frontendUrl.'/payment/cancel',
                'client_reference_id' => (string) $reservation->id,
                'metadata' => [
                    'reservation_id' => (string) $reservation->id,
                ],
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => $data['currency'],
                            'unit_amount' => (int) round(((float) $data['final_price']) * 100),
                            'product_data' => [
                                'name' => $data['title'],
                                'metadata' => [
                                    'people_count' => (string) $reservation->accommodations_count,
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->throw()
            ->json();

        $reservation->update([
            'stripe_session_id' => $response['id'],
            'total_price' => $data['final_price'],
        ]);

        return response()->json([
            'data' => [
                'checkout_url' => $response['url'],
            ],
        ]);
    }

    private function frontendUrl(StripeCheckoutRequest $request): string
    {
        foreach (['X-Frontend-Origin', 'Origin'] as $header) {
            $origin = (string) $request->header($header, '');

            if (Str::startsWith($origin, ['http://', 'https://'])) {
                return rtrim($origin, '/');
            }
        }

        return rtrim((string) config('app.url'), '/');
    }
}
