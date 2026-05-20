<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['num', 'nb_lits', 'building_id', 'floor', 'surface', 'view', 'balcony'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'balcony' => false,
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class)->latest('id');
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balcony' => 'boolean',
        ];
    }
}
