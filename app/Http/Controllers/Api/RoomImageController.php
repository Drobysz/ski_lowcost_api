<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomImageController extends Controller
{
    public function show(Image $image): StreamedResponse|Response
    {
        abort_if(! $image->path, 404);

        $disk = Storage::disk('s3');

        abort_unless($disk->exists($image->path), 404);

        $stream = $disk->readStream($image->path);

        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Disposition' => 'inline; filename="'.$image->name.'"',
            'Content-Type' => $disk->mimeType($image->path) ?: 'application/octet-stream',
        ]);
    }
}
