<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'room_id' => $this->room_id,
            'client_id' => $this->client_id,
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
