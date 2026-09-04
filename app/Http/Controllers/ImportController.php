<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\StoreImportResource;
use App\Services\ImportService;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function store(StoreImportRequest $request)
    {
        $validated = $request->validated();
        $validated['sent_at'] = $request->sentAt();

        [$import, $created] = $this->importService->createImport($validated);
        $data = StoreImportResource::make($import);

        return response()->json(['data' => $data], $created ? 202 : 200);
    }
}
