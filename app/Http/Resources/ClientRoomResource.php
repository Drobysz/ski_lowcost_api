<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $room = $this->room;
        $reservation = $this->reservation;

        return [
            'id' => $room?->id,
            'num' => $room?->num,
            'nb_lits' => $room?->nb_lits,
            'building_id' => $room?->building_id,
            'floor' => $room?->floor,
            'surface' => $room?->surface,
            'view' => $room?->view,
            'balcony' => $room?->balcony,
            'images' => ImageResource::collection($room?->images ?? collect()),
            'reservation' => [
                'id' => $reservation?->id,
                'check_in' => $reservation?->check_in?->toDateTimeString(),
                'check_out' => $reservation?->check_out?->toDateTimeString(),
                'status' => $reservation?->status,
            ],
            'created_at' => $room?->created_at?->toISOString(),
            'updated_at' => $room?->updated_at?->toISOString(),
        ];
    }
}
