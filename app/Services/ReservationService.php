<?php

namespace App\Services;

use App\Exceptions\ReservationConflictException;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReservationService
{
    /**
     * @throws ModelNotFoundException
     * @throws ReservationConflictException
     * @throws Throwable
     */
    public function createReservation(int $offerId, array $validated): Reservation
    {
        $reservation = DB::transaction(callback: function () use ($offerId, $validated) {
            $offer = Offer::query()
                ->lockForUpdate()
                ->find($offerId);
            if (!$offer) {
                throw new ModelNotFoundException('Offer not found.');
            }

            if ($offer->available_units <= 0) {
                throw new ReservationConflictException('Offer is no longer available.');
            }

            if ($offer->expires_at <= now()) {
                throw new ReservationConflictException('Offer has expired.');
            }

            $reservation = Reservation::query()->create([
                'offer_id' => $offer->id,
                'client_reference' => $validated['client_reference'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
            ]);

            $offer->decrement('available_units');
            return $reservation;
        });

        return $reservation->loadMissing([
            'offer',
            'offer.supplier',
            'offer.property',
        ]);
    }
}
