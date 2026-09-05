<?php

namespace App\Http\Controllers;

use App\Exceptions\ReservationConflictException;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\ReservationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {
    }

    public function store(int $offerId, StoreReservationRequest $request)
    {
        $validated = $request->validated();

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $reservation = $this->reservationService->createReservation($offerId, $validated);

            return ReservationResource::make($reservation)
                ->response()
                ->setStatusCode(201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Offer not found.',
            ], 404);

        } catch (ReservationConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}
