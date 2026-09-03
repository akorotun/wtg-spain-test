<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $external_import_id
 * @property ImportStatus $status
 * @property int $total_offers
 * @property int $processed_offers
 * @property string|null $error
 * @property Carbon $sent_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Supplier $supplier
 * @property-read ImportPayload|null $importPayload
 */

class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'status',
        'total_offers',
        'processed_offers',
        'error',
        'sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'supplier_id' => 'integer',
            'external_import_id' => 'string',
            'status' => ImportStatus::class,
            'total_offers' => 'integer',
            'processed_offers' => 'integer',
            'error' => 'string',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function importPayload(): HasOne
    {
        return $this->hasOne(ImportPayload::class);
    }
}
