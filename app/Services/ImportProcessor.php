<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;
use RuntimeException;

class ImportProcessor
{
    /**
     * @throws Throwable
     */
    public function process(int $importId): void
    {
        Import::query()
            ->whereKey($importId)
            ->whereIn('status', [
                ImportStatus::Pending,
                ImportStatus::Processing,
            ])
            ->update([
                'status' => ImportStatus::Processing,
            ]);

        DB::transaction(function () use ($importId) {

            $import = Import::with(['importPayload', 'supplier'])
                ->lockForUpdate()
                ->find($importId);
            if (!$import) {
                // import відсутній - retry → retry → failed()
                throw new RuntimeException('Import not found.');
            }

            // import не потребує обробки
            if (in_array($import->status, [
                ImportStatus::Completed,
                ImportStatus::Failed,
            ], true)) {
                return;
            }

            $importPayload = $import->importPayload;
            if (!$importPayload) {
                // importPayload відсутній - retry → retry → failed()
                throw new RuntimeException('Import payload not found.');
            }

            $supplier = $import->supplier;
            if (!$supplier) {
                // supplier відсутній - retry → retry → failed()
                throw new RuntimeException('Supplier not found.');
            }

            $offers = $importPayload->offers;
            if (empty($offers)) {
                // offers пустий - retry → retry → failed()
                throw new RuntimeException('Import payload contains no offers.');
            }

            // отримуємо всі properties з offers
            $properties = collect($offers)
                ->pluck('property')
                ->unique('code')
                ->values();

            // створюємо або оновлюємо одним запитом properties
            Property::query()->upsert(
                $properties->all(),
                ['code'],
                ['name', 'city'],
            );

            // тягнемо всі properties які є в offers імпорту з бази для отримання property_id для offers
            $propertyByCode = Property::query()
                ->whereIn('code', $properties->pluck('code'))
                ->get()
                ->keyBy('code');

            // будуємо масив offers для наступного upsert
            $offerRows = collect($offers)->map(function ($offer) use ($supplier, $propertyByCode) {
                return [
                    'supplier_id' => $supplier->id,
                    'external_id' => $offer['external_id'],
                    'property_id' => $propertyByCode[$offer['property']['code']]->id,
                    'check_in' => $offer['check_in'],
                    'check_out' => $offer['check_out'],
                    'max_guests' => $offer['max_guests'],
                    'price' => $offer['price'],
                    'currency' => $offer['currency'],
                    'available_units' => $offer['available_units'],
                    'expires_at' => CarbonImmutable::parse($offer['expires_at'])
                        ->utc()
                        ->toDateTimeString(),
                ];
            });

            // створюємо або оновлюємо одним запитом offers
            Offer::query()->upsert(
                $offerRows->all(),
                ['supplier_id', 'external_id'],
                [
                    'property_id',
                    'check_in',
                    'check_out',
                    'max_guests',
                    'price',
                    'currency',
                    'available_units',
                    'expires_at',
                ],
            );

            $import->update([
                'status' => ImportStatus::Completed,
                'processed_offers' => count($offers),
                'completed_at' => now(),
            ]);
        });
    }
}
