<?php

namespace App\Models;

use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $offer_id
 * @property string $client_reference
 * @property string $customer_name
 * @property string $customer_email
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Offer $offer
 */

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'client_reference',
        'customer_name',
        'customer_email',
    ];

    protected function casts(): array
    {
        return [
            'offer_id' => 'integer',
            'client_reference' => 'string',
            'customer_name' => 'string',
            'customer_email' => 'string',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
