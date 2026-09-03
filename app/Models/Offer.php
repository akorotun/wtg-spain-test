<?php

namespace App\Models;

use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $supplier_id
 * @property int $property_id
 * @property string $external_id
 * @property Carbon $check_in
 * @property Carbon $check_out
 * @property int $max_guests
 * @property int $price
 * @property string $currency
 * @property int $available_units
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Supplier $supplier
 * @property-read Property $property
 * @property-read Collection<int, Reservation> $reservations
 */

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'property_id',
        'external_id',
        'check_in',
        'check_out',
        'max_guests',
        'price',
        'currency',
        'available_units',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'supplier_id' => 'integer',
            'property_id' => 'integer',
            'external_id' => 'string',
            'check_in' => 'date',
            'check_out' => 'date',
            'max_guests' => 'integer',
            'price' => 'integer',
            'currency' => 'string',
            'available_units' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
