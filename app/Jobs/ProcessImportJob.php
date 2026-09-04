<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\ImportProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId
    ) {
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(ImportProcessor $importProcessor): void
    {
        $importProcessor->process($this->importId);
    }

    public function failed(?Throwable $exception): void
    {
        Import::query()
            ->whereKey($this->importId)
            ->where('status', '!=', ImportStatus::Completed)
            ->update([
                'status' => ImportStatus::Failed,
                'processed_offers' => 0,
                'error' => $exception?->getMessage(),
                'completed_at' => null,
            ]);
    }
}
