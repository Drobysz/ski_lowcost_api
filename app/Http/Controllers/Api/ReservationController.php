<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Reservation::query()
            ->with(['client', 'accommodations.room.images', 'accommodations.client'])
            ->latest('id');

        if ($request->user() instanceof Client) {
            $query->where('client_id', $request->user()->getKey());
        }

        return ReservationResource::collection($query->paginate());
    }

    public function store(StoreReservationRequest $request): ReservationResource
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user instanceof Client && (int) $data['client_id'] !== (int) $user->getKey()) {
            abort(403);
        }

        $reservation = DB::transaction(function () use ($data, $user): Reservation {
            $roomIds = collect($data['accommodations'])->pluck('room_id')->unique()->values();

            Room::query()->whereKey($roomIds)->lockForUpdate()->get();
            $this->ensureRoomsAreAvailable($roomIds, $data['check_in'], $data['check_out']);

            $attributes = Arr::only($data, ['client_id', 'check_in', 'check_out', 'total_price']);
            $attributes['status'] = $user instanceof Admin ? ($data['status'] ?? 'not paid') : 'not paid';

            $reservation = Reservation::create($attributes);
            $reservation->accommodations()->createMany($data['accommodations']);

            return $reservation;
        });

        return new ReservationResource($reservation->load(['client', 'accommodations.room.images', 'accommodations.client']));
    }

    public function show(Request $request, Reservation $reservation): ReservationResource
    {
        $this->authorizeReservationAccess($request, $reservation);

        return new ReservationResource($reservation->load(['client', 'accommodations.room.images', 'accommodations.client']));
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $this->authorizeReservationAccess($request, $reservation);

        $data = $request->validated();

        DB::transaction(function () use ($data, $reservation): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey());

            $checkIn = $data['check_in'] ?? $lockedReservation->check_in;
            $checkOut = $data['check_out'] ?? $lockedReservation->check_out;
            $roomIds = array_key_exists('accommodations', $data)
                ? collect($data['accommodations'])->pluck('room_id')->filter()->unique()->values()
                : $lockedReservation->accommodations()->pluck('room_id')->filter()->unique()->values();

            if (
                $roomIds->isNotEmpty() &&
                (
                    array_key_exists('check_in', $data) ||
                    array_key_exists('check_out', $data) ||
                    array_key_exists('accommodations', $data)
                )
            ) {
                Room::query()->whereKey($roomIds)->lockForUpdate()->get();
                $this->ensureRoomsAreAvailable($roomIds, $checkIn, $checkOut, $lockedReservation);
            }

            $lockedReservation->update(Arr::only($data, [
                'client_id',
                'check_in',
                'check_out',
                'purchase_date',
                'status',
                'total_price',
                'stripe_session_id',
                'paid_at',
            ]));

            if (array_key_exists('accommodations', $data)) {
                $lockedReservation->accommodations()->delete();
                $lockedReservation->accommodations()->createMany($data['accommodations']);
            }
        });

        return new ReservationResource($reservation->refresh()->load(['client', 'accommodations.room.images', 'accommodations.client']));
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeReservationAccess($request, $reservation);

        DB::transaction(function () use ($reservation): void {
            Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->getKey())
                ->update(['status' => 'cancelled']);
        });

        return response()->json(['ok' => true]);
    }

    private function authorizeReservationAccess(Request $request, Reservation $reservation): void
    {
        $user = $request->user();

        abort_unless($user instanceof Admin || ($user instanceof Client && (int) $reservation->client_id === (int) $user->getKey()), 403);
    }

    /**
     * @param  Collection<int, int>  $roomIds
     */
    private function ensureRoomsAreAvailable(Collection $roomIds, mixed $checkIn, mixed $checkOut, ?Reservation $exceptReservation = null): void
    {
        $query = Reservation::query()
            ->where('status', '!=', 'cancelled')
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->whereHas('accommodations', fn ($query) => $query->whereIn('room_id', $roomIds));

        if ($exceptReservation instanceof Reservation) {
            $query->whereKeyNot($exceptReservation->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'accommodations' => ['One or more selected rooms are not available for the requested date range.'],
            ]);
        }
    }
}
