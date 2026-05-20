<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccommodationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Accommodation::query()
            ->with(['reservation', 'room.images', 'client'])
            ->latest('id');

        if ($request->user() instanceof Client) {
            $clientId = $request->user()->getKey();
            $query->where(function ($query) use ($clientId): void {
                $query->where('client_id', $clientId)
                    ->orWhereHas('reservation', fn ($reservationQuery) => $reservationQuery->where('client_id', $clientId));
            });
        }

        return AccommodationResource::collection($query->paginate());
    }

    public function store(StoreAccommodationRequest $request): AccommodationResource
    {
        $data = $request->validated();

        $accommodation = DB::transaction(function () use ($data, $request): Accommodation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($data['reservation_id']);
            $this->authorizeReservationOwner($request, $reservation);

            Room::query()->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $this->ensureRoomIsAvailable($data['room_id'], $reservation);

            return Accommodation::create($data);
        });

        return new AccommodationResource($accommodation->load(['reservation', 'room.images', 'client']));
    }

    public function show(Request $request, Accommodation $accommodation): AccommodationResource
    {
        $this->authorizeAccommodationAccess($request, $accommodation);

        return new AccommodationResource($accommodation->load(['reservation', 'room.images', 'client']));
    }

    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): AccommodationResource
    {
        $this->authorizeAccommodationAccess($request, $accommodation);
        $data = $request->validated();

        DB::transaction(function () use ($accommodation, $data): void {
            $lockedAccommodation = Accommodation::query()->lockForUpdate()->findOrFail($accommodation->getKey());
            $reservationId = $data['reservation_id'] ?? $lockedAccommodation->reservation_id;
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservationId);

            if (array_key_exists('room_id', $data)) {
                Room::query()->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
                $this->ensureRoomIsAvailable($data['room_id'], $reservation, $lockedAccommodation);
            }

            $lockedAccommodation->update($data);
        });

        return new AccommodationResource($accommodation->refresh()->load(['reservation', 'room.images', 'client']));
    }

    public function destroy(Request $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorizeAccommodationAccess($request, $accommodation);
        $accommodation->delete();

        return response()->json(null, 204);
    }

    private function authorizeAccommodationAccess(Request $request, Accommodation $accommodation): void
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            return;
        }

        abort_unless(
            $user instanceof Client
            && ((int) $accommodation->client_id === (int) $user->getKey() || (int) $accommodation->reservation->client_id === (int) $user->getKey()),
            403,
        );
    }

    private function authorizeReservationOwner(Request $request, Reservation $reservation): void
    {
        $user = $request->user();

        abort_unless($user instanceof Admin || ($user instanceof Client && (int) $reservation->client_id === (int) $user->getKey()), 403);
    }

    private function ensureRoomIsAvailable(int $roomId, Reservation $reservation, ?Accommodation $exceptAccommodation = null): void
    {
        $query = Accommodation::query()
            ->where('room_id', $roomId)
            ->whereHas('reservation', function ($query) use ($reservation): void {
                $query->where('status', '!=', 'cancelled')
                    ->where('check_in', '<', $reservation->check_out)
                    ->where('check_out', '>', $reservation->check_in);
            });

        if ($exceptAccommodation instanceof Accommodation) {
            $query->whereKeyNot($exceptAccommodation->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'room_id' => ['The selected room is not available for the reservation date range.'],
            ]);
        }
    }
}
