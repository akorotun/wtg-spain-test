<?php

namespace App\Services\Handlers;

use Illuminate\Pagination\Paginator;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginationProcessor
{
    /**
     * @param class-string<JsonResource> $resource
     */
    public function process(Paginator $paginator, string $resource): array
    {
        $paginator->withQueryString();

        return [
            'data' => $resource::collection($paginator->items()),
            'next' => $paginator->nextPageUrl(),
            'prev' => $paginator->previousPageUrl(),
            'per_page' => $paginator->perPage(),
        ];
    }
}
