<?php

namespace App\Services;

use App\Dto\PropertySearchDto;
use App\Models\Offer;
use Illuminate\Pagination\Paginator;

class PropertyService
{
    public function indexProperties(PropertySearchDto $dto): Paginator
    {
        $city = $dto->city;
        // внутрішній SELECT: фільтрує актуальні Offer та визначає їх рейтинг усередині кожного Property
        $rankedOffers = Offer::query()
            // поверне всі поля Offer + обчислене поле row_num
            ->select('offers.*')
            // ROW_NUMBER не відкидає рядки, що пройшли WHERE
            // для кожного property_id нумерує Offer за ціною
            // id використовується як вирішальний критерій при однаковій ціні
            ->selectRaw('
                ROW_NUMBER() OVER (
                    PARTITION BY offers.property_id
                    ORDER BY offers.price ASC, offers.id ASC
                ) AS row_num
            ')
            ->where('check_in', $dto->check_in)
            ->where('check_out', $dto->check_out)
            ->where('max_guests', '>=', $dto->guests)
            ->where('available_units', '>', 0)
            ->where('expires_at', '>', now())
            ->when($city, function ($query) use ($city) {
                $query->whereHas('property', function ($query) use ($city) {
                    $query->where('city', $city);
                });
            });

        // зовнішній SELECT працює з rankedOffers як з підзапитом
        $query = Offer::query()
            // використовує $rankedOffers як підзапит у FROM з псевдонімом 'offers'
            ->fromSub($rankedOffers, 'offers')
            ->with([
                'property',
                'supplier',
            ])
            // залишає тільки найдешевший Offer для кожного Property
            ->where('row_num', 1)
            ->orderBy('price')
            ->orderBy('id');

        return $query->simplePaginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
