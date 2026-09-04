<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ShowImportResource;
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

        return StoreImportResource::make($import)
            ->response()
            ->setStatusCode($created ? 202 : 200);
    }

    public function show(int $importId)
    {
        $import = $this->importService->showImport($importId);
        if (!$import) {
            return response()->json([
                'message' => 'Import not found.',
            ], 404);
        }

        return ShowImportResource::make($import);

    }

}
