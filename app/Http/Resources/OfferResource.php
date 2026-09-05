<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Offer
 */
class OfferResource extends JsonResource
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
            'external_id' => $this->external_id,
            'check_in' => $this->check_in?->toDateString(),
            'check_out' => $this->check_out?->toDateString(),
            'max_guests' => $this->max_guests,
            'price' => $this->price,
            'currency' => $this->currency,
            'available_units' => $this->available_units,
            'expires_at' => $this->expires_at?->toIso8601ZuluString(),

            'supplier' => $this->supplier->code,

            'property' => [
                'code' => $this->property->code,
                'name' => $this->property->name,
                'city' => $this->property->city,
            ]
        ];
    }
}
