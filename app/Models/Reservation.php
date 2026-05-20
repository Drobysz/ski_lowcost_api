<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'check_in',
    'check_out',
    'purchase_date',
    'status',
    'total_price',
    'stripe_session_id',
    'paid_at',
])]
class Reservation extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'not paid',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'purchase_date' => 'datetime',
            'paid_at' => 'datetime',
            'total_price' => 'decimal:2',
        ];
    }
}
