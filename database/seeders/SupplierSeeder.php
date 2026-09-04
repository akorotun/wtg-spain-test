<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::query()->updateOrCreate(
            ['code' => 'supplier-a'],
            []
        );

        Supplier::query()->updateOrCreate(
            ['code' => 'supplier-b'],
            []
        );
    }
}
