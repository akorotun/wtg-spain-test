<?php

namespace App\Models;

use Database\Factories\ImportPayloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $import_id
 * @property array $offers
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Import $import
 */

class ImportPayload extends Model
{
    /** @use HasFactory<ImportPayloadFactory> */
    use HasFactory;

    protected $fillable = [
        'import_id',
        'offers',
    ];

    protected function casts(): array
    {
        return [
            'import_id' => 'integer',
            'offers' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
