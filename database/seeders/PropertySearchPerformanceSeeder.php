<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySearchPerformanceSeeder extends Seeder
{
    private const PROPERTY_COUNT = 5_000;
    private const PROPERTY_PREFIX = 'PERF-';
    private const PROPERTY_BATCH_SIZE = 1_000;
    private const OFFER_BATCH_SIZE = 1_000;

    private const CITIES = [
        'Barcelona' => 0.50,
        'Madrid' => 0.30,
        'Valencia' => 0.20,
    ];

    private const BASE_DATE = '2026-10-10';
    private const CHECK_OUT_DATE = '2026-10-15';

    public function run(): void
    {
        $now = CarbonImmutable::now();
        $future = $now->addYear();
        $past = $now->subDay();

        $supplierA = Supplier::query()->where('code', 'supplier-a')->firstOrFail();
        $supplierB = Supplier::query()->where('code', 'supplier-b')->firstOrFail();

        $this->cleanupPerformanceData();

        $this->command?->info('Creating performance properties and offers...');

        $offerBuffer = [];
        $offerCount = 0;

        for ($propertyIndex = 1; $propertyIndex <= self::PROPERTY_COUNT; $propertyIndex++) {
            $city = $this->pickCity($propertyIndex);
            $propertyCode = self::PROPERTY_PREFIX . str_pad((string) $propertyIndex, 6, '0', STR_PAD_LEFT);

            $propertyRows[] = [
                'code' => $propertyCode,
                'name' => "Performance Property {$propertyIndex}",
                'city' => $city,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($propertyIndex % self::PROPERTY_BATCH_SIZE === 0 || $propertyIndex === self::PROPERTY_COUNT) {
                DB::table('properties')->insert($propertyRows);

                $insertedIds = DB::table('properties')
                    ->where('code', 'like', self::PROPERTY_PREFIX . '%')
                    ->orderByDesc('id')
                    ->limit(count($propertyRows))
                    ->pluck('id')
                    ->reverse()
                    ->values();

                foreach ($insertedIds as $offset => $propertyId) {
                    $currentPropertyIndex = $propertyIndex - count($propertyRows) + $offset + 1;
                    $currentPropertyCode = $propertyRows[$offset]['code'];
                    $currentCity = $propertyRows[$offset]['city'];

                    $offers = $this->buildOffersForProperty(
                        $propertyId,
                        $currentPropertyCode,
                        $currentPropertyIndex,
                        $supplierA->id,
                        $supplierB->id,
                        $now,
                        $future,
                        $past,
                    );

                    foreach ($offers as $offer) {
                        $offerBuffer[] = $offer;
                        $offerCount++;

                        if (count($offerBuffer) >= self::OFFER_BATCH_SIZE) {
                            DB::table('offers')->insert($offerBuffer);
                            $offerBuffer = [];
                            $this->command?->info("Inserted {$offerCount} offers...");
                        }
                    }
                }

                $propertyRows = [];
            }
        }

        if (!empty($offerBuffer)) {
            DB::table('offers')->insert($offerBuffer);
        }

        $this->command?->info('Done.');
        $this->command?->info('Properties created: ' . self::PROPERTY_COUNT);
        $this->command?->info('Offers created: ' . $offerCount);
    }

    private function cleanupPerformanceData(): void
    {
        $this->command?->info('Cleaning up previous performance data...');

        $propertyIds = DB::table('properties')
            ->where('code', 'like', self::PROPERTY_PREFIX . '%')
            ->pluck('id');

        if ($propertyIds->isNotEmpty()) {
            DB::table('offers')->whereIn('property_id', $propertyIds)->delete();
            DB::table('properties')->whereIn('id', $propertyIds)->delete();
        }
    }

    private function pickCity(int $index): string
    {
        $bucket = $index % 10;

        return match (true) {
            $bucket < 5 => 'Barcelona',
            $bucket < 8 => 'Madrid',
            default => 'Valencia',
        };
    }

    private function buildOffersForProperty(
        int $propertyId,
        string $propertyCode,
        int $propertyIndex,
        int $supplierAId,
        int $supplierBId,
        CarbonImmutable $now,
        CarbonImmutable $future,
        CarbonImmutable $past,
    ): array {
        $baseExternalId = "{$propertyCode}-offer";
        $isTieCase = $propertyIndex % 10 === 0;

        $offers = [
            // 1. Valid offer (min price)
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-1",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                70000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 2. Valid offer, higher price
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-2",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                75000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 3. Valid offer, even higher price
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-3",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                90000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 4. Wrong check_in
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-4",
                '2026-10-20',
                '2026-10-25',
                4,
                70000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 5. Wrong check_out
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-5",
                '2026-10-05',
                '2026-10-09',
                4,
                70000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 6. max_guests < 2
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-6",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                1,
                70000,
                'EUR',
                2,
                $future,
                $now,
            ),
            // 7. available_units = 0
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-7",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                70000,
                'EUR',
                0,
                $future,
                $now,
            ),
            // 8. Expired offer
            $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-8",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                70000,
                'EUR',
                2,
                $past,
                $now,
            ),
            // 9. Valid offer from supplier-b
            $this->offerRow(
                $supplierBId,
                $propertyId,
                "{$baseExternalId}-b",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                80000,
                'EUR',
                2,
                $future,
                $now,
            ),
        ];

        if ($isTieCase) {
            // Extra valid offer with the same min price to test deterministic ROW_NUMBER by id
            $offers[] = $this->offerRow(
                $supplierAId,
                $propertyId,
                "{$baseExternalId}-tie",
                self::BASE_DATE,
                self::CHECK_OUT_DATE,
                4,
                70000,
                'EUR',
                2,
                $future,
                $now,
            );
        }

        return $offers;
    }

    private function offerRow(
        int $supplierId,
        int $propertyId,
        string $externalId,
        string $checkIn,
        string $checkOut,
        int $maxGuests,
        int $price,
        string $currency,
        int $availableUnits,
        CarbonImmutable $expiresAt,
        CarbonImmutable $now,
    ): array {
        return [
            'supplier_id' => $supplierId,
            'property_id' => $propertyId,
            'external_id' => $externalId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'max_guests' => $maxGuests,
            'price' => $price,
            'currency' => $currency,
            'available_units' => $availableUnits,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
