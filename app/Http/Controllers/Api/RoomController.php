<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\ClientRoomResource;
use App\Http\Resources\RoomResource;
use App\Models\Accommodation;
use App\Models\Client;
use App\Models\Image;
use App\Models\Room;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RoomController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoomResource::collection(Room::query()->with('images')->latest('id')->paginate());
    }

    public function my(Request $request): AnonymousResourceCollection
    {
        /** @var Client $client */
        $client = $request->user();

        $accommodations = Accommodation::query()
            ->select('accommodations.*')
            ->with(['room.images', 'reservation'])
            ->whereNotNull('room_id')
            ->join('reservations', 'reservations.id', '=', 'accommodations.reservation_id')
            ->where('reservations.client_id', $client->getKey())
            ->orderByDesc('reservations.check_in')
            ->orderByDesc('reservations.id')
            ->orderBy('accommodations.id')
            ->get();

        return ClientRoomResource::collection($accommodations);
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = DB::transaction(function () use ($request): Room {
            $room = Room::create($request->safe()->except('images'));
            $this->storeImages($room, $request->file('images'));

            return $room;
        });

        return new RoomResource($room->load('images'));
    }

    public function show(Room $room): RoomResource
    {
        return new RoomResource($room->load('images'));
    }

    public function update(UpdateRoomRequest $request, Room $room): RoomResource
    {
        DB::transaction(function () use ($request, $room): void {
            $room->update($request->safe()->except('images'));
            $this->storeImages($room, $request->file('images'));
        });

        return new RoomResource($room->refresh()->load('images'));
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json(null, 204);
    }

    /**
     * @param  UploadedFile|array<int, UploadedFile>|null  $images
     */
    private function storeImages(Room $room, UploadedFile|array|null $images): void
    {
        foreach ($this->validUploadedImages($images) as $image) {
            $disk = $this->roomImageDisk();
            $directory = "rooms/{$room->id}";
            $name = $this->uniqueImageName($disk, $directory, $image);
            $path = $disk->putFileAs($directory, $image, $name);

            Image::create([
                'room_id' => $room->id,
                'name' => $name,
                'path' => $path,
                'url' => null,
            ]);
        }
    }

    private function roomImageDisk(): Filesystem
    {
        if (! config('filesystems.disks.s3.bucket')) {
            throw new RuntimeException('AWS S3 bucket is not configured.');
        }

        return Storage::disk('s3');
    }

    private function uniqueImageName(Filesystem $disk, string $directory, UploadedFile $image): string
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $image->getClientOriginalExtension();
        $baseName = Str::slug($originalName) ?: 'image';
        $extension = $extension ? ".{$extension}" : '';
        $name = "{$baseName}{$extension}";
        $counter = 2;

        while ($disk->exists("{$directory}/{$name}")) {
            $name = "{$baseName}-{$counter}{$extension}";
            $counter += 1;
        }

        return $name;
    }

    /**
     * @param  UploadedFile|array<int, UploadedFile>|null  $images
     * @return array<int, UploadedFile>
     */
    private function validUploadedImages(UploadedFile|array|null $images): array
    {
        $files = is_array($images) ? $images : array_filter([$images]);

        foreach ($files as $image) {
            if ($image instanceof UploadedFile && ! $image->isValid()) {
                throw ValidationException::withMessages([
                    'images' => ['Image upload failed. Restart the API with higher PHP upload limits and try again.'],
                ]);
            }
        }

        return array_values(array_filter(
            $files,
            fn (mixed $image): bool => $image instanceof UploadedFile && $image->isValid(),
        ));
    }
}
