<?php

namespace Tests\Feature\Imports;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\ImportPayload;
use App\Models\Supplier;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StoreImportTest extends TestCase
{
    public function test_it_creates_import_and_dispatches_job(): void
    {
        Bus::fake();

        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $payload = $this->validPayload([
            'supplier' => $supplier->code,
        ]);

        $response = $this->postJson(route('imports.store'), $payload);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', ImportStatus::Pending->value)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('imports', [
            'supplier_id' => $supplier->id,
            'external_import_id' => $payload['external_import_id'],
            'status' => ImportStatus::Pending->value,
            'total_offers' => 1,
            'processed_offers' => 0,
        ]);

        $importPayload = ImportPayload::query()
            ->where('import_id', $response->json('data.id'))
            ->first();

        $this->assertNotNull($importPayload);
        $this->assertSame('offer-1', $importPayload->offers[0]['external_id']);

        Bus::assertDispatched(ProcessImportJob::class);
    }

    public function test_it_returns_existing_import_and_does_not_dispatch_job_again(): void
    {
        Bus::fake();

        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $payload = $this->validPayload([
            'supplier' => $supplier->code,
        ]);

        $this->postJson(route('imports.store'), $payload)->assertStatus(202);
        Bus::assertDispatched(ProcessImportJob::class, 1);

        $response = $this->postJson(route('imports.store'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ImportStatus::Pending->value);

        $this->assertDatabaseCount('imports', 1);
        $this->assertDatabaseCount('import_payloads', 1);
        Bus::assertDispatched(ProcessImportJob::class, 1);
    }

    public function test_it_requires_a_valid_supplier(): void
    {
        Bus::fake();

        $payload = $this->validPayload([
            'supplier' => 'non-existing-supplier',
        ]);

        $response = $this->postJson(route('imports.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplier']);

        $this->assertDatabaseCount('imports', 0);
        $this->assertDatabaseCount('import_payloads', 0);
        Bus::assertNotDispatched(ProcessImportJob::class);
    }

    public function test_it_validates_required_fields(): void
    {
        Bus::fake();

        $response = $this->postJson(route('imports.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'supplier',
                'external_import_id',
                'sent_at',
                'offers',
            ]);

        Bus::assertNotDispatched(ProcessImportJob::class);
    }

    public function test_it_validates_offer_structure(): void
    {
        Bus::fake();

        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);

        $response = $this->postJson(route('imports.store'), [
            'supplier' => $supplier->code,
            'external_import_id' => 'ext-1',
            'sent_at' => '2026-09-04T12:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-1',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'offers.0.check_in',
                'offers.0.check_out',
                'offers.0.max_guests',
                'offers.0.price',
                'offers.0.currency',
                'offers.0.available_units',
                'offers.0.expires_at',
                'offers.0.property',
            ]);

        Bus::assertNotDispatched(ProcessImportJob::class);
    }

    public function test_it_rejects_more_than_fifty_offers(): void
    {
        Bus::fake();

        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $offers = array_fill(0, 51, $this->validPayload()['offers'][0]);

        $response = $this->postJson(route('imports.store'), [
            'supplier' => $supplier->code,
            'external_import_id' => 'ext-1',
            'sent_at' => '2026-09-04T12:00:00Z',
            'offers' => $offers,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offers']);

        Bus::assertNotDispatched(ProcessImportJob::class);
    }

    public function test_it_rejects_invalid_sent_at(): void
    {
        Bus::fake();

        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);

        $response = $this->postJson(route('imports.store'), [
            'supplier' => $supplier->code,
            'external_import_id' => 'ext-1',
            'sent_at' => 'not-a-date',
            'offers' => $this->validPayload()['offers'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sent_at']);

        Bus::assertNotDispatched(ProcessImportJob::class);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'supplier' => 'supplier-1',
            'external_import_id' => 'external-import-1',
            'sent_at' => '2026-09-04T12:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-1',
                    'check_in' => '2026-10-01',
                    'check_out' => '2026-10-07',
                    'max_guests' => 4,
                    'price' => 10000,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-09-10T12:00:00Z',
                    'property' => [
                        'code' => 'prop-1',
                        'name' => 'Test Property',
                        'city' => 'Test City',
                    ],
                ],
            ],
        ], $overrides);
    }
}
