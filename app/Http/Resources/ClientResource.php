<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'age' => $this->age,
            'address' => $this->address,
            'birth_date' => $this->birth_date?->toDateString(),
            'tel' => $this->tel,
            'skiing_level' => $this->skiing_level,
            'height' => $this->height,
            'weight' => $this->weight,
            'shoe_size' => $this->shoe_size,
            'role' => $this->role,
            'reservations' => ReservationResource::collection($this->whenLoaded('reservations')),
            'accommodations' => AccommodationResource::collection($this->whenLoaded('accommodations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
