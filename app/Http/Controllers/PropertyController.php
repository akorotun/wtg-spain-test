<?php

namespace App\Http\Controllers;

use App\Dto\PropertySearchDto;
use App\Http\Requests\IndexPropertyRequest;
use App\Http\Resources\IndexPropertyResource;
use App\Services\Handlers\PaginationProcessor;
use App\Services\PropertyService;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
        private readonly PaginationProcessor $paginationProcessor,
    ) {
    }

    public function index(IndexPropertyRequest $request)
    {
        $dto = PropertySearchDto::fromArray($request->validated());
        $paginator = $this->propertyService->indexProperties($dto);
        $data = $this->paginationProcessor->process($paginator, IndexPropertyResource::class);

        return response()->json($data);
    }

}
