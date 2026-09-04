<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\ImportPayload;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportService
{
    /**
     * @throws Throwable
     */
    public function createImport(array $validated)
    {
        $supplierCode = $validated['supplier'];
        $supplier = Supplier::query()->where('code', $supplierCode)->first();
        if (!$supplier) {
            throw ValidationException::withMessages([
                'supplier' => ['The selected supplier is invalid.'],
            ]);
        }

        return DB::transaction(function () use ($supplier, $validated)  {
            $importOffers = $validated['offers'];

            $import = Import::query()->createOrFirst(
                [
                    'supplier_id' => $supplier->id,
                    'external_import_id' => $validated['external_import_id'],
                ],
                [
                    'status' => ImportStatus::Pending,
                    'total_offers' => count($importOffers),
                    'processed_offers' => 0,
                    'error' => null,
                    'sent_at' => $validated['sent_at'],
                    'completed_at' => null,
                ],
            );

            if (! $import->wasRecentlyCreated) {
                // Такий import уже існував. Payload не створюємо. Job повторно не запускаємо.
                return [$import, false];
            }

            ImportPayload::query()->create([
                'import_id' => $import->id,
                'offers' => $importOffers,
            ]);

            ProcessImportJob::dispatch($import->id)->afterCommit();

            return [$import, true];
        });
    }

    public function showImport(int $importId): ?Import
    {
        return Import::query()
            ->with('supplier')
            ->find($importId);
    }
}
