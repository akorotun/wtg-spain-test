<?php

namespace Tests\Feature\Properties;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class IndexPropertyTest extends TestCase
{
    public function test_it_returns_properties_with_best_offer(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'price' => 90000,
        ]);
        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-2',
            'price' => 70000,
        ]);
        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-3',
            'price' => 75000,
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $property->code)
            ->assertJsonPath('data.0.best_offer.price', 70000)
            ->assertJsonPath('data.0.best_offer.supplier', 'supplier-a');
    }

    public function test_it_filters_by_city(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $barcelonaProperty = $this->createProperty('Barcelona');
        $madridProperty = $this->createProperty('Madrid');

        $this->createOffer($barcelonaProperty, $supplier);
        $this->createOffer($madridProperty, $supplier);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'Barcelona');
    }

    public function test_it_filters_by_guests(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'max_guests' => 1,
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_filters_by_available_units(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'available_units' => 0,
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_filters_by_expired_offer(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'expires_at' => CarbonImmutable::now()->subDay(),
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_filters_by_check_in_and_check_out(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'check_in' => '2026-10-20',
            'check_out' => '2026-10-25',
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_returns_deterministic_best_offer_when_prices_are_tied(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $cheapestOffer = $this->createOffer($property, $supplier, [
            'external_id' => 'offer-1',
            'price' => 70000,
        ]);

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-2',
            'price' => 70000,
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.best_offer.id', $cheapestOffer->id);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->getJson(route('properties.index'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'check_in',
                'check_out',
                'guests',
            ]);
    }

    public function test_it_validates_date_format(): void
    {
        $response = $this->getJson(route('properties.index', [
            'check_in' => '10-10-2026',
            'check_out' => '15-10-2026',
            'guests' => 2,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'check_in',
                'check_out',
            ]);
    }

    public function test_it_returns_empty_when_no_matches(): void
    {
        $response = $this->getJson(route('properties.index', [
            'city' => 'NonExistingCity',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_paginates_results(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);

        $property1 = $this->createProperty('Barcelona');
        $property2 = $this->createProperty('Barcelona');
        $property3 = $this->createProperty('Barcelona');

        $this->createOffer($property1, $supplier, ['external_id' => 'offer-1', 'price' => 70000]);
        $this->createOffer($property2, $supplier, ['external_id' => 'offer-2', 'price' => 80000]);
        $this->createOffer($property3, $supplier, ['external_id' => 'offer-3', 'price' => 90000]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
            'per_page' => 2,
            'page' => 1,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('per_page', 2);

        $this->assertNotNull($response->json('next'));
        $this->assertNull($response->json('prev'));
    }

    public function test_pagination_preserves_search_query_string(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);

        $property1 = $this->createProperty('Barcelona');
        $property2 = $this->createProperty('Barcelona');
        $property3 = $this->createProperty('Barcelona');

        $this->createOffer($property1, $supplier, ['external_id' => 'offer-1', 'price' => 70000]);
        $this->createOffer($property2, $supplier, ['external_id' => 'offer-2', 'price' => 80000]);
        $this->createOffer($property3, $supplier, ['external_id' => 'offer-3', 'price' => 90000]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
            'per_page' => 2,
            'page' => 1,
        ]));

        $next = $response->json('next');
        $this->assertStringContainsString('city=Barcelona', $next);
        $this->assertStringContainsString('check_in=2026-10-10', $next);
        $this->assertStringContainsString('check_out=2026-10-15', $next);
        $this->assertStringContainsString('guests=2', $next);
        $this->assertStringContainsString('per_page=2', $next);
        $this->assertStringContainsString('page=2', $next);
    }

    public function test_it_sorts_properties_by_best_offer_price(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);

        $propertyExpensive = $this->createProperty('Barcelona');
        $propertyCheap = $this->createProperty('Barcelona');
        $propertyMedium = $this->createProperty('Barcelona');

        $this->createOffer($propertyExpensive, $supplier, ['external_id' => 'offer-1', 'price' => 90000]);
        $this->createOffer($propertyCheap, $supplier, ['external_id' => 'offer-2', 'price' => 70000]);
        $this->createOffer($propertyMedium, $supplier, ['external_id' => 'offer-3', 'price' => 80000]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.best_offer.price', 70000)
            ->assertJsonPath('data.1.best_offer.price', 80000)
            ->assertJsonPath('data.2.best_offer.price', 90000);
    }

    public function test_it_ignores_cheaper_invalid_offer_when_selecting_best_offer(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);
        $property = $this->createProperty('Barcelona');

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-invalid',
            'price' => 50000,
            'available_units' => 0,
        ]);

        $this->createOffer($property, $supplier, [
            'external_id' => 'offer-valid',
            'price' => 70000,
        ]);

        $response = $this->getJson(route('properties.index', [
            'city' => 'Barcelona',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.best_offer.price', 70000);
    }

    public function test_it_searches_all_cities_when_city_is_not_provided(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-a']);

        $barcelonaProperty = $this->createProperty('Barcelona');
        $madridProperty = $this->createProperty('Madrid');

        $this->createOffer($barcelonaProperty, $supplier);
        $this->createOffer($madridProperty, $supplier);

        $response = $this->getJson(route('properties.index', [
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'guests' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    private function createProperty(string $city): Property
    {
        return Property::factory()->create([
            'code' => 'PROP-' . uniqid(),
            'name' => 'Test Property',
            'city' => $city,
        ]);
    }

    private function createOffer(Property $property, Supplier $supplier, array $overrides = []): Offer
    {
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
