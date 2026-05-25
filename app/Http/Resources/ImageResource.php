<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
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
            'room_id' => $this->room_id,
            'name' => $this->name,
            'path' => $this->path,
            'url' => $this->isPlaceholder() ? null : url("/ski_lowcost_api/room-images/{$this->id}"),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function isPlaceholder(): bool
    {
        return $this->name === 'placeholder.jpg' ||
            str_ends_with((string) $this->path, '/placeholder.jpg') ||
            str_contains((string) $this->url, 'example.com');
    }
}
