<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Collection<int, Import> $imports
 * @property-read Collection<int, Offer> $offers
 */

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    protected function casts(): array
    {
        return [
            'code' => 'string',
        ];
    }

    public function imports()
    {
        return $this->hasMany(Import::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}
