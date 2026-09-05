<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Offer
 */
class IndexPropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $property = $this->property;
        return [
            'code' => $property->code,
            'name' => $property->name,
            'city' => $property->city,
            'best_offer' => [
                'id' => $this->id,
                'supplier' => $this->supplier->code,
                'price' => $this->price,
                'currency' => $this->currency,
                'available_units' => $this->available_units,
                'expires_at' => $this->expires_at,
            ]

        ];
    }
}
