<?php

namespace Tests\Feature\Reservations;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class StoreReservationTest extends TestCase
{
    public function test_it_creates_reservation_successfully(): void
    {
        $offer = $this->createOffer(['available_units' => 5]);

        $payload = [
            'client_reference' => 'REF-' . uniqid(),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'client_reference',
                    'customer_name',
                    'customer_email',
                    'offer' => [
                        'id',
                        'supplier',
                        'property',
                        'price',
                        'currency',
                        'available_units',
                        'expires_at',
                    ],
                ],
            ])
            ->assertJsonPath('data.client_reference', $payload['client_reference'])
            ->assertJsonPath('data.customer_name', $payload['customer_name'])
            ->assertJsonPath('data.customer_email', $payload['customer_email'])
            ->assertJsonPath('data.offer.id', $offer->id);

        $this->assertDatabaseHas('reservations', [
            'offer_id' => $offer->id,
            'client_reference' => $payload['client_reference'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
        ]);

        $offer->refresh();
        $this->assertSame(4, $offer->available_units);
    }

    public function test_it_books_last_available_unit_and_rejects_second_booking(): void
    {
        $offer = $this->createOffer(['available_units' => 1]);

        $firstPayload = [
            'client_reference' => 'REF-FIRST-' . uniqid(),
            'customer_name' => 'First Customer',
            'customer_email' => 'first@example.com',
        ];

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), $firstPayload);
        $response->assertStatus(201);

        $offer->refresh();
        $this->assertSame(0, $offer->available_units);

        $secondPayload = [
            'client_reference' => 'REF-SECOND-' . uniqid(),
            'customer_name' => 'Second Customer',
            'customer_email' => 'second@example.com',
        ];

        $secondResponse = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), $secondPayload);

        $secondResponse->assertStatus(409)
            ->assertJson([
                'message' => 'Offer is no longer available.',
            ]);

        $this->assertDatabaseCount('reservations', 1);

        $offer->refresh();
        $this->assertSame(0, $offer->available_units);
    }

    public function test_it_returns_409_when_no_available_units(): void
    {
        $offer = $this->createOffer(['available_units' => 0]);

        $payload = [
            'client_reference' => 'REF-' . uniqid(),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), $payload);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Offer is no longer available.',
            ]);

        $this->assertDatabaseCount('reservations', 0);

        $offer->refresh();
        $this->assertSame(0, $offer->available_units);
    }

    public function test_it_returns_409_when_offer_expired(): void
    {
        $offer = $this->createOffer([
            'available_units' => 5,
            'expires_at' => CarbonImmutable::now()->subDay(),
        ]);

        $payload = [
            'client_reference' => 'REF-' . uniqid(),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), $payload);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Offer has expired.',
            ]);

        $this->assertDatabaseCount('reservations', 0);

        $offer->refresh();
        $this->assertSame(5, $offer->available_units);
    }

    public function test_it_returns_404_when_offer_not_found(): void
    {
        $payload = [
            'client_reference' => 'REF-' . uniqid(),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson(route('reservation.store', ['offerId' => 999999]), $payload);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Offer not found.',
            ]);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_it_validates_required_fields(): void
    {
        $offer = $this->createOffer(['available_units' => 5]);

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'client_reference',
                'customer_name',
                'customer_email',
            ]);

        $this->assertDatabaseCount('reservations', 0);

        $offer->refresh();
        $this->assertSame(5, $offer->available_units);
    }

    public function test_it_validates_customer_email_format(): void
    {
        $offer = $this->createOffer(['available_units' => 5]);

        $response = $this->postJson(route('reservation.store', ['offerId' => $offer->id]), [
            'client_reference' => 'REF-' . uniqid(),
            'customer_name' => 'John Doe',
            'customer_email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);

        $this->assertDatabaseCount('reservations', 0);

        $offer->refresh();
        $this->assertSame(5, $offer->available_units);
    }

    private function createOffer(array $overrides = []): Offer
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = Property::factory()->create([
            'code' => 'PROP-' . uniqid(),
            'name' => 'Test Property',
            'city' => 'Barcelona',
        ]);

        return Offer::factory()->create(array_merge([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'external_id' => 'OFFER-' . uniqid(),
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 70000,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => CarbonImmutable::now()->addYear(),
        ], $overrides));
    }
}
