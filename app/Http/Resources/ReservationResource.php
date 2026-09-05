<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
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
            'client_reference' => $this->client_reference,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'offer' => OfferResource::make($this->offer),
        ];
    }
}
