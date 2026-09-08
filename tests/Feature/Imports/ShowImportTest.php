<?php

namespace Tests\Feature\Imports;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Supplier;
use Tests\TestCase;

class ShowImportTest extends TestCase
{
    public function test_it_returns_existing_import(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
            'external_import_id' => 'external-import-1',
            'status' => ImportStatus::Completed,
            'total_offers' => 10,
            'processed_offers' => 10,
            'error' => null,
            'sent_at' => '2026-09-04 12:00:00',
            'completed_at' => '2026-09-04 12:01:00',
        ]);

        $response = $this->getJson(route('imports.show', ['importId' => $import->id]));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.supplier', 'supplier-1')
            ->assertJsonPath('data.external_import_id', 'external-import-1')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_offers', 10)
            ->assertJsonPath('data.processed_offers', 10)
            ->assertJsonPath('data.error', null)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'supplier',
                    'external_import_id',
                    'sent_at',
                    'status',
                    'total_offers',
                    'processed_offers',
                    'error',
                    'created_at',
                    'completed_at',
                ],
            ]);
    }

    public function test_it_returns_404_for_non_existing_import(): void
    {
        $response = $this->getJson(route('imports.show', ['importId' => 999999]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Import not found.',
            ]);
    }

    public function test_it_returns_404_for_non_numeric_import(): void
    {
        $response = $this->getJson('/api/imports/abc');

        $response->assertStatus(404);
    }

    public function test_it_returns_import_with_failed_status(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'supplier-1']);
        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
            'external_import_id' => 'external-import-failed',
            'status' => ImportStatus::Failed,
            'total_offers' => 5,
            'processed_offers' => 0,
            'error' => 'Something went wrong',
            'sent_at' => '2026-09-04 12:00:00',
            'completed_at' => null,
        ]);

        $response = $this->getJson(route('imports.show', ['importId' => $import->id]));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.total_offers', 5)
            ->assertJsonPath('data.processed_offers', 0)
            ->assertJsonPath('data.error', 'Something went wrong')
            ->assertJsonPath('data.completed_at', null);
    }
}
