<?php

namespace Tests\Feature\Services;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\ImportPayload;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Services\ImportProcessor;
use Tests\TestCase;

class ImportProcessorTest extends TestCase
{
    public function test_it_creates_property_and_offer_and_marks_import_completed(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $import = $this->createPendingImport($supplier, [
            $this->offerData(['external_id' => 'offer-1']),
        ]);

        $processor = new ImportProcessor();
        $processor->process($import->id);

        $import->refresh();
        $this->assertEquals(ImportStatus::Completed, $import->status);
        $this->assertEquals(1, $import->processed_offers);
        $this->assertEquals($import->total_offers, $import->processed_offers);
        $this->assertNotNull($import->completed_at);

        $this->assertDatabaseHas('properties', [
            'code' => 'prop-1',
            'name' => 'Test Property',
            'city' => 'Test City',
        ]);

        $this->assertDatabaseHas('offers', [
            'supplier_id' => $supplier->id,
            'external_id' => 'offer-1',
            'max_guests' => 4,
            'price' => 10000,
            'currency' => 'EUR',
            'available_units' => 2,
        ]);
    }

    public function test_it_does_not_duplicate_properties_with_same_code_in_one_import(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $import = $this->createPendingImport($supplier, [
            $this->offerData(['external_id' => 'offer-1']),
            $this->offerData([
                'external_id' => 'offer-2',
                'property' => [
                    'code' => 'prop-1',
                    'name' => 'Another Name',
                    'city' => 'Another City',
                ],
            ]),
        ]);

        $processor = new ImportProcessor();
        $processor->process($import->id);

        $this->assertDatabaseCount('properties', 1);

        $property = Property::query()->where('code', 'prop-1')->first();
        $this->assertNotNull($property);
        $this->assertSame('Test Property', $property->name);
        $this->assertSame('Test City', $property->city);
    }

    public function test_it_updates_existing_property(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        Property::factory()->create([
            'code' => 'prop-1',
            'name' => 'Old Name',
            'city' => 'Old City',
        ]);

        $import = $this->createPendingImport($supplier, [
            $this->offerData(['external_id' => 'offer-1']),
        ]);

        $processor = new ImportProcessor();
        $processor->process($import->id);

        $this->assertDatabaseCount('properties', 1);

        $property = Property::query()->where('code', 'prop-1')->first();
        $this->assertSame('Test Property', $property->name);
        $this->assertSame('Test City', $property->city);
    }

    public function test_it_updates_existing_offer(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $property = Property::factory()->create([
            'code' => 'prop-1',
            'name' => 'Test Property',
            'city' => 'Test City',
        ]);

        Offer::factory()->create([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'external_id' => 'offer-1',
            'check_in' => '2026-01-01',
            'check_out' => '2026-01-07',
            'max_guests' => 2,
            'price' => 5000,
            'currency' => 'USD',
            'available_units' => 1,
            'expires_at' => '2026-01-10 12:00:00',
        ]);

        $import = $this->createPendingImport($supplier, [
            $this->offerData(['external_id' => 'offer-1']),
        ]);

        $processor = new ImportProcessor();
        $processor->process($import->id);

        $this->assertDatabaseCount('offers', 1);

        $offer = Offer::query()
            ->where('supplier_id', $supplier->id)
            ->where('external_id', 'offer-1')
            ->first();

        $this->assertSame('2026-10-01', $offer->check_in->format('Y-m-d'));
        $this->assertSame('2026-10-07', $offer->check_out->format('Y-m-d'));
        $this->assertSame(4, $offer->max_guests);
        $this->assertSame(10000, $offer->price);
        $this->assertSame('EUR', $offer->currency);
        $this->assertSame(2, $offer->available_units);
    }

    public function test_job_failed_method_marks_import_as_failed(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $import = $this->createPendingImport($supplier, [
            $this->offerData(['external_id' => 'offer-1']),
        ]);

        $job = new ProcessImportJob($import->id);
        $job->failed(new \RuntimeException('Something went wrong'));

        $import->refresh();
        $this->assertEquals(ImportStatus::Failed, $import->status);
        $this->assertSame(0, $import->processed_offers);
        $this->assertSame('Something went wrong', $import->error);
        $this->assertNull($import->completed_at);
    }

    private function createPendingImport(Supplier $supplier, array $offers): Import
    {
        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
            'external_import_id' => 'external-import-1',
            'status' => ImportStatus::Pending,
            'total_offers' => count($offers),
            'processed_offers' => 0,
            'error' => null,
            'sent_at' => now(),
            'completed_at' => null,
        ]);

        ImportPayload::factory()->create([
            'import_id' => $import->id,
            'offers' => $offers,
        ]);

        return $import;
    }

    private function offerData(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}
