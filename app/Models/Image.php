<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_id', 'name', 'path', 'url'])]
class Image extends Model
{
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
