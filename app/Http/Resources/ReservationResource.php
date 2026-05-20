<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'client_id' => $this->client_id,
            'check_in' => $this->check_in?->toDateTimeString(),
            'check_out' => $this->check_out?->toDateTimeString(),
            'purchase_date' => $this->purchase_date?->toDateTimeString(),
            'status' => $this->status,
            'total_price' => $this->total_price,
            'stripe_session_id' => $this->stripe_session_id,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'accommodations' => AccommodationResource::collection($this->whenLoaded('accommodations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
